<?php

declare(strict_types=1);

/**
 * Cria uma copia SQLite contendo somente os registros ligados a um CPF.
 * O banco de origem nunca e alterado.
 *
 * Uso:
 *   php install/backup-user.php --source=arquivo.sqlite --cpf=CPF --output=pasta
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Este arquivo deve ser executado somente pelo terminal.\n");
}

$options = getopt('', ['source:', 'cpf:', 'output::']);
$source = trim((string)($options['source'] ?? ''));
$cpf = preg_replace('/\D+/', '', (string)($options['cpf'] ?? ''));
$output = trim((string)($options['output'] ?? ''));

if ($source === '' || $cpf === '') {
    fwrite(STDERR, "Informe --source=arquivo.sqlite e --cpf=CPF.\n");
    exit(2);
}
if (!is_file($source)) {
    fwrite(STDERR, "Banco SQLite nao encontrado: {$source}\n");
    exit(3);
}

if ($output === '') {
    $output = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR
        . 'user-' . $cpf . '-' . date('Ymd-His') . DIRECTORY_SEPARATOR . 'financeiro.sqlite';
}

$output = rtrim($output, "\\/");
$outputDir = pathinfo($output, PATHINFO_EXTENSION) === '' ? $output : dirname($output);
if (is_file($output) || is_dir($output)) {
    fwrite(STDERR, "O destino ja existe; nenhum arquivo foi sobrescrito: {$output}\n");
    exit(4);
}
if (!is_dir($outputDir) && !mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
    fwrite(STDERR, "Nao foi possivel criar o destino: {$outputDir}\n");
    exit(5);
}

if (!copy($source, $output)) {
    fwrite(STDERR, "Nao foi possivel copiar o banco de origem.\n");
    exit(6);
}

try {
    $pdo = new PDO('sqlite:' . $output, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = OFF');

    $userColumns = array_column($pdo->query('PRAGMA table_info("users")')->fetchAll(), 'name');
    if (!in_array('cpf', $userColumns, true)) {
        throw new RuntimeException('A tabela users nao possui a coluna cpf.');
    }

    $userStatement = $pdo->prepare('SELECT id FROM users WHERE cpf = :cpf ORDER BY id');
    $userStatement->execute([':cpf' => $cpf]);
    $userIds = array_map('intval', array_column($userStatement->fetchAll(), 'id'));
    if ($userIds === []) {
        throw new RuntimeException('Nenhum usuario encontrado para o CPF informado.');
    }

    $tables = $pdo->query(
        "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
    )->fetchAll(PDO::FETCH_COLUMN);
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $tableReport = [];

    foreach ($tables as $table) {
        $table = (string)$table;
        $quoted = quote_sqlite_identifier($table);
        $columns = $pdo->query('PRAGMA table_info(' . $quoted . ')')->fetchAll();
        $columnNames = array_column($columns, 'name');

        if ($table === 'users') {
            $delete = $pdo->prepare('DELETE FROM ' . $quoted . ' WHERE id NOT IN (' . $placeholders . ')');
            $delete->execute($userIds);
            $tableReport[$table] = (int)$pdo->query('SELECT COUNT(*) FROM ' . $quoted)->fetchColumn();
            continue;
        }

        if (in_array('user_id', $columnNames, true)) {
            $delete = $pdo->prepare('DELETE FROM ' . $quoted . ' WHERE user_id NOT IN (' . $placeholders . ')');
            $delete->execute($userIds);
        }

        $tableReport[$table] = (int)$pdo->query('SELECT COUNT(*) FROM ' . $quoted)->fetchColumn();
    }

    $pdo->exec('PRAGMA foreign_keys = ON');
    $integrity = (string)$pdo->query('PRAGMA integrity_check')->fetchColumn();
    if ($integrity !== 'ok') {
        throw new RuntimeException("Integridade do backup: {$integrity}");
    }

    $manifest = [
        'created_at' => date('c'),
        'source_file' => basename($source),
        'cpf_filter' => $cpf,
        'matched_user_ids' => $userIds,
        'database_file' => basename($output),
        'integrity' => $integrity,
        'rows_by_table' => $tableReport,
        'note' => 'Copia filtrada; o banco de origem nao foi alterado.',
    ];
    file_put_contents(
        dirname($output) . DIRECTORY_SEPARATOR . 'backup-manifest.json',
        json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL
    );

    fwrite(STDOUT, "Backup filtrado criado com sucesso.\n");
    fwrite(STDOUT, "Usuarios encontrados: " . count($userIds) . "\n");
    fwrite(STDOUT, "Pasta: " . dirname($output) . "\n");
} catch (Throwable $error) {
    fwrite(STDERR, "Backup filtrado incompleto: {$error->getMessage()}\n");
    exit(7);
}

function quote_sqlite_identifier(string $identifier): string
{
    return '"' . str_replace('"', '""', $identifier) . '"';
}
