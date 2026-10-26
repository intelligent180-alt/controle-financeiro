<?php

declare(strict_types=1);

/*
 * Preparador nao destrutivo para hospedagem.
 *
 * Este arquivo nunca executa DELETE, DROP, TRUNCATE ou reset de usuarios.
 * O schema MySQL usado pelo bootstrap possui apenas CREATE TABLE IF NOT EXISTS.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Instalador disponivel somente pelo terminal.\n");
}

require_once __DIR__ . '/../api/bootstrap.php';
require_once __DIR__ . '/legacy-migration.php';

$options = getopt('', ['prepare', 'check', 'migrate', 'source:', 'cpf:', 'source-user-id:', 'profile::', 'confirm:', 'base-url:', 'help']);

if (isset($options['help']) || (!$options && PHP_SAPI === 'cli')) {
    fwrite(STDOUT, <<<TEXT
Preparador de hospedagem do Controle Financeiro

Uso seguro:
  php install/hosting-install.php --check
  php install/hosting-install.php --prepare --base-url=https://seu-dominio.example/
  php install/hosting-install.php --migrate --source=data/financeiro.sqlite --cpf=CPF
  php install/hosting-install.php --migrate --source=data/financeiro.sqlite --cpf=CPF --profile=clt --confirm=MIGRAR-DADOS-SEM-APAGAR

--check    valida PHP, extensoes e arquivos sem conectar ao banco.
--prepare  conecta ao MySQL e cria somente tabelas ausentes.
--migrate  mostra a previa do SQLite legado; com --confirm importa de forma aditiva.
--source-user-id seleciona o ID da conta legada quando o CPF aparece mais de uma vez.
--base-url informa o dominio que sera usado pelo APK; nao altera o banco.

Nenhuma opcao apaga dados ou altera usuarios existentes.
TEXT . PHP_EOL);
    exit(0);
}

function hosting_line(string $label, string $value): void
{
    fwrite(STDOUT, str_pad($label, 24, ' ') . $value . PHP_EOL);
}

function hosting_fail(string $message, int $status = 2): never
{
    fwrite(STDERR, "ERRO: {$message}" . PHP_EOL);
    exit($status);
}

function hosting_base_url(?string $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }
    if (!filter_var($value, FILTER_VALIDATE_URL)) {
        hosting_fail('base-url invalida. Use https://seu-dominio.example/');
    }
    $scheme = strtolower((string)parse_url($value, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        hosting_fail('base-url deve iniciar com http:// ou https://.');
    }
    return rtrim($value, '/') . '/';
}

function hosting_required_files(): array
{
    return [
        'index.html',
        'login.html',
        '.htaccess',
        'api/bootstrap.php',
        'api/auth.php',
        'api/finance.php',
        'api/health.php',
        'api/professional.php',
        'css/financeiro.css',
        'js/financeiro.js',
        'js/professional-modules.js',
        'service-worker.js',
        'install/mysql-schema.sql',
        'mobile/android/app/build.gradle',
    ];
}

$baseUrl = hosting_base_url($options['base-url'] ?? null);
$root = dirname(__DIR__);
$config = sf_database_config();

if (isset($options['migrate'])) {
    try {
        $source = (string)($options['source'] ?? ($root . '/data/financeiro.sqlite'));
        $cpf = (string)($options['cpf'] ?? '');
        $sourceUserId = isset($options['source-user-id']) ? (int)$options['source-user-id'] : null;
        $profile = (string)($options['profile'] ?? 'keep');
        $confirmation = (string)($options['confirm'] ?? '');
        $execute = $confirmation !== '';
        $migration = sf_legacy_migration_run($source, $cpf, $profile, $execute, $confirmation, $sourceUserId);
        hosting_line('Migracao', $migration['message']);
        hosting_line('Usuarios encontrados', (string)$migration['users_found']);
        if (!empty($migration['source_user_ids'])) {
            hosting_line('IDs na origem', implode(', ', $migration['source_user_ids']));
        }
        hosting_line('Perfil solicitado', $profile);
        if (!empty($migration['warning'])) {
            hosting_line('Atencao', $migration['warning']);
        }
        foreach ($migration['rows_by_table'] as $table => $count) {
            hosting_line('  ' . $table, (string)$count . ' registro(s)');
        }
        if (!$execute && count($migration['source_user_ids']) > 1 && $sourceUserId === null) {
            fwrite(STDOUT, PHP_EOL . "Escolha um ID da origem e rode a previa novamente:" . PHP_EOL);
            fwrite(STDOUT, "  php install/hosting-install.php --migrate --source=" . $source
                . " --cpf=" . $cpf . " --source-user-id=ID --profile=" . $profile . PHP_EOL);
        } elseif (!$execute) {
            fwrite(STDOUT, PHP_EOL . "Para executar depois de revisar a previa:" . PHP_EOL);
            fwrite(STDOUT, "  php install/hosting-install.php --migrate --source=" . $source
                . " --cpf=" . $cpf
                . ($sourceUserId !== null ? " --source-user-id=" . $sourceUserId : '')
                . " --profile=" . $profile
                . " --confirm=MIGRAR-DADOS-SEM-APAGAR" . PHP_EOL);
        }
        exit(0);
    } catch (Throwable $error) {
        hosting_fail('Falha na migracao: ' . $error->getMessage(), 1);
    }
}

hosting_line('PHP', PHP_VERSION);
hosting_line('Driver configurado', (string)$config['driver']);
hosting_line('Base URL', $baseUrl !== '' ? $baseUrl : '(nao informada)');

if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    hosting_fail('PHP 8.1 ou superior e necessario.');
}

foreach (['json', 'mbstring'] as $extension) {
    if (!extension_loaded($extension)) {
        hosting_fail("Extensao PHP ausente: {$extension}.");
    }
    hosting_line("Extensao {$extension}", 'OK');
}

if (!is_file($root . '/config/config.local.php') && $config['driver'] === 'mysql') {
    $environmentConfigured = getenv('SF_DB_NAME') || getenv('DB_DATABASE') || getenv('DB_NAME');
    if (!$environmentConfigured) {
        hosting_fail('Crie config/config.local.php a partir de config/config.example.php ou configure as variaveis SF_DB_*/DB_*.');
    }
}

if (isset($options['prepare'])) {
    if ($config['driver'] !== 'mysql') {
        hosting_fail('O modo --prepare exige driver mysql para evitar preparar acidentalmente o banco local SQLite.');
    }
    if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
        hosting_fail('A extensao PDO MySQL nao esta habilitada nesta hospedagem.');
    }

    try {
        $pdo = sf_db();
        $tables = [
            'users', 'cards', 'transactions', 'incomes', 'goals', 'imports',
            'bank_connections', 'professional_assets', 'professional_taxes',
            'professional_clients', 'professional_catalog',
            'professional_cash_accounts', 'professional_categories',
            'professional_payment_methods', 'professional_fiscal_documents',
            'professional_settings', 'professional_permissions',
            'professional_schedule', 'professional_credit_checks',
        ];
        $missing = [];
        foreach ($tables as $table) {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name'
            );
            $stmt->execute([':table_name' => $table]);
            if ((int)$stmt->fetchColumn() === 0) {
                $missing[] = $table;
            }
        }
        if ($missing) {
            hosting_fail('O schema nao foi criado completamente. Tabelas ausentes: ' . implode(', ', $missing));
        }

        $users = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        hosting_line('MySQL', 'CONEXAO OK');
        hosting_line('Tabelas', count($tables) . ' OK');
        hosting_line('Usuarios preservados', (string)$users);
    } catch (Throwable $error) {
        hosting_fail('Falha na preparacao MySQL: ' . $error->getMessage(), 1);
    }
} else {
    hosting_line('Banco', 'nao alterado (use --prepare para criar tabelas ausentes)');
}

$missingFiles = [];
foreach (hosting_required_files() as $relative) {
    if (!is_file($root . '/' . $relative)) {
        $missingFiles[] = $relative;
    }
}
if ($missingFiles) {
    hosting_fail('Arquivos do sistema ausentes: ' . implode(', ', $missingFiles));
}
hosting_line('Arquivos web/mobile', 'OK');

if ($baseUrl !== '') {
    fwrite(STDOUT, PHP_EOL . "APK preconfigurado com este dominio:" . PHP_EOL);
    fwrite(STDOUT, "  cd mobile/android" . PHP_EOL);
    fwrite(STDOUT, "  gradlew.bat assembleDebug -PappServerUrl={$baseUrl}" . PHP_EOL);
}

fwrite(STDOUT, PHP_EOL . "Preparacao concluida sem apagar ou modificar dados existentes." . PHP_EOL);
