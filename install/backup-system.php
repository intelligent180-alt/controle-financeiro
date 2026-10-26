<?php

declare(strict_types=1);

/**
 * Cria um snapshot local do sistema e do banco, sem executar reset ou apagar
 * dados. Funciona tanto com o SQLite local quanto com o MySQL configurado.
 *
 * Uso:
 *   php install/backup-system.php --source=sqlite
 *   php install/backup-system.php --source=mysql
 *   php install/backup-system.php --source=auto
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Este arquivo deve ser executado somente pelo terminal.\n");
}

$root = dirname(__DIR__);
$options = getopt('', ['source::', 'output::']);
$source = strtolower(trim((string)($options['source'] ?? 'auto')));

if (!in_array($source, ['auto', 'sqlite', 'mysql'], true)) {
    fwrite(STDERR, "Fonte invalida. Use --source=auto, --source=sqlite ou --source=mysql.\n");
    exit(2);
}

$version = is_file($root . DIRECTORY_SEPARATOR . 'VERSION')
    ? trim((string)file_get_contents($root . DIRECTORY_SEPARATOR . 'VERSION'))
    : 'desconhecida';

if ($source === 'auto') {
    $configFile = $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.local.php';
    $config = is_file($configFile) ? require $configFile : [];
    $source = strtolower((string)($config['database']['driver'] ?? getenv('SF_DB_DRIVER') ?: 'sqlite'));
    if (!in_array($source, ['sqlite', 'mysql'], true)) {
        $source = 'sqlite';
    }
}

$requestedOutput = trim((string)($options['output'] ?? ''));
$destination = $requestedOutput !== ''
    ? resolve_output_path($root, $requestedOutput)
    : $root . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . 'system-' . date('Ymd-His');

if (is_dir($destination) || is_file($destination)) {
    fwrite(STDERR, "O destino ja existe; nenhum arquivo foi sobrescrito: {$destination}\n");
    exit(3);
}

if (!mkdir($destination, 0775, true) && !is_dir($destination)) {
    fwrite(STDERR, "Nao foi possivel criar o destino do backup: {$destination}\n");
    exit(4);
}

$manifest = [
    'app' => 'Sistema Financeiro',
    'version' => $version,
    'created_at' => date('c'),
    'database_source' => $source,
    'config_local_included' => false,
    'notes' => [
        'Backup criado sem apagar ou alterar dados do sistema.',
        'O arquivo config/config.local.php foi omitido por conter credenciais.',
        'Para restaurar, configure novamente o banco em config/config.local.php.',
    ],
];

try {
    copy_project_files($root, $destination);
    file_put_contents(
        $destination . DIRECTORY_SEPARATOR . 'CONFIG_LOCAL_NAO_INCLUIDO.txt',
        "O arquivo config/config.local.php nao foi copiado por conter credenciais.\n"
        . "Depois de restaurar os arquivos, crie esse arquivo novamente usando config/config.example.php.\n"
    );

    if ($source === 'sqlite') {
        $sqlitePath = $root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'financeiro.sqlite';
        $backupDatabase = $destination . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'financeiro.sqlite';
        $manifest['database_file'] = 'database/financeiro.sqlite';
        $manifest['database_integrity'] = backup_sqlite($sqlitePath, $backupDatabase);
    } else {
        $mysqlDump = $destination . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'mysql-dump.sql';
        $mysqlResult = backup_mysql($root, $mysqlDump);
        $manifest['database_file'] = 'database/mysql-dump.sql';
        $manifest['mysql_tables'] = $mysqlResult['tables'];
        $manifest['mysql_table_count'] = count($mysqlResult['tables']);
    }

    file_put_contents(
        $destination . DIRECTORY_SEPARATOR . 'backup-manifest.json',
        json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL
    );

    fwrite(STDOUT, "Backup criado com sucesso.\n");
    fwrite(STDOUT, "Fonte: {$source}\n");
    fwrite(STDOUT, "Pasta: {$destination}\n");
    fwrite(STDOUT, "O backup nao altera o banco original.\n");
} catch (Throwable $error) {
    $errorFile = $destination . DIRECTORY_SEPARATOR . 'BACKUP_ERRO.txt';
    file_put_contents(
        $errorFile,
        "O backup ficou incompleto. Nenhum banco original foi apagado.\n"
        . "Detalhe tecnico: " . $error->getMessage() . "\n"
    );
    fwrite(STDERR, "Backup incompleto: {$error->getMessage()}\n");
    fwrite(STDERR, "Arquivos parciais mantidos em: {$destination}\n");
    exit(5);
}

function resolve_output_path(string $root, string $requested): string
{
    $path = $requested;
    if (!preg_match('/^[A-Za-z]:[\\\\\/]/', $path) && !str_starts_with($path, DIRECTORY_SEPARATOR)) {
        $path = $root . DIRECTORY_SEPARATOR . $path;
    }
    return rtrim($path, "\\/");
}

function copy_project_files(string $root, string $destination): void
{
    $excludedDirectories = [
        '.git',
        'backup',
        'data',
        'node_modules',
        'build',
        '.gradle',
        '.idea',
    ];
    $excludedFiles = [
        'config/config.local.php',
        'install/patch-20260911.php',
    ];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $absolute = $item->getPathname();
        $relative = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, substr($absolute, strlen($root))), DIRECTORY_SEPARATOR);
        $relativeParts = explode(DIRECTORY_SEPARATOR, $relative);

        if (array_intersect($relativeParts, $excludedDirectories) !== []) {
            continue;
        }

        if ($item->isDir()) {
            continue;
        }

        if (in_array(str_replace(DIRECTORY_SEPARATOR, '/', $relative), $excludedFiles, true)) {
            continue;
        }

        $extension = strtolower((string)pathinfo($absolute, PATHINFO_EXTENSION));
        if (in_array($extension, ['sqlite', 'sql', 'apk', 'aab'], true)) {
            continue;
        }

        $target = $destination . DIRECTORY_SEPARATOR . $relative;
        $targetDirectory = dirname($target);
        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
            throw new RuntimeException("Nao foi possivel criar {$targetDirectory}");
        }
        if (!copy($absolute, $target)) {
            throw new RuntimeException("Nao foi possivel copiar {$relative}");
        }
    }
}

function backup_sqlite(string $source, string $destination): string
{
    if (!is_file($source)) {
        throw new RuntimeException("Banco SQLite nao encontrado: {$source}");
    }

    $directory = dirname($destination);
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException("Nao foi possivel criar {$directory}");
    }

    $pdo = new PDO('sqlite:' . $source, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec('VACUUM INTO ' . $pdo->quote($destination));

    if (!is_file($destination)) {
        throw new RuntimeException('O snapshot SQLite nao foi criado.');
    }

    $integrity = (string)$pdo->query('PRAGMA integrity_check')->fetchColumn();
    if ($integrity !== 'ok') {
        throw new RuntimeException("Integridade SQLite: {$integrity}");
    }
    return $integrity;
}

function backup_mysql(string $root, string $destination): array
{
    require_once $root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'bootstrap.php';
    $config = sf_database_config();
    if ($config['driver'] !== 'mysql') {
        throw new RuntimeException('A configuracao atual nao esta usando MySQL.');
    }
    if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
        throw new RuntimeException('A extensao PDO MySQL nao esta habilitada neste PHP.');
    }
    if ((string)$config['database'] === '' || (string)$config['username'] === '') {
        throw new RuntimeException('Configure banco, usuario e senha antes do backup MySQL.');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );
    $pdo = new PDO($dsn, (string)$config['username'], (string)$config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $directory = dirname($destination);
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException("Nao foi possivel criar {$directory}");
    }

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $handle = fopen($destination, 'wb');
    if ($handle === false) {
        throw new RuntimeException("Nao foi possivel criar {$destination}");
    }

    fwrite($handle, "-- Backup do Sistema Financeiro\n");
    fwrite($handle, '-- Criado em ' . date('c') . "\n");
    fwrite($handle, "-- Este arquivo e um dump de restauracao; ele nao foi executado pelo backup.\n\n");
    fwrite($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

    foreach ($tables as $table) {
        $quotedTable = quote_mysql_identifier((string)$table);
        $createRow = $pdo->query('SHOW CREATE TABLE ' . $quotedTable)->fetch(PDO::FETCH_NUM);
        $createSql = (string)($createRow[1] ?? '');
        fwrite($handle, "DROP TABLE IF EXISTS {$quotedTable};\n");
        fwrite($handle, $createSql . ";\n\n");

        $rows = $pdo->query('SELECT * FROM ' . $quotedTable);
        while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
            $columns = array_map('quote_mysql_identifier', array_keys($row));
            $values = [];
            foreach ($row as $value) {
                $values[] = $value === null ? 'NULL' : $pdo->quote((string)$value);
            }
            fwrite(
                $handle,
                'INSERT INTO ' . $quotedTable . ' (' . implode(', ', $columns) . ') VALUES ('
                . implode(', ', $values) . ");\n"
            );
        }
        fwrite($handle, "\n");
    }

    fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($handle);

    return ['tables' => array_map('strval', $tables)];
}

function quote_mysql_identifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}
