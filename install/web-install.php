<?php

declare(strict_types=1);

/*
 * Instalador web de primeiro uso.
 *
 * Ele fica protegido por install.token, aceita apenas HTTPS em hospedagem
 * publica, cria o schema de forma aditiva e grava config.local.php somente
 * depois de validar a conexao MySQL.
 */

$root = dirname(__DIR__);
$tokenFile = __DIR__ . '/install.token';
$lockFile = __DIR__ . '/install.lock';

function web_install_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function web_install_fail(string $message): never
{
    throw new RuntimeException($message);
}

function web_install_source_path(string $root, string $source): string
{
    $source = trim(str_replace('\\', '/', $source));
    if ($source === '' || str_contains($source, "\0")) {
        web_install_fail('Informe um arquivo SQLite legado valido.');
    }
    $rootPath = realpath($root);
    $sourcePath = realpath($root . '/' . ltrim($source, '/'));
    if ($rootPath === false || $sourcePath === false || !is_file($sourcePath)) {
        web_install_fail('Arquivo SQLite legado nao encontrado dentro do projeto.');
    }
    $prefix = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (!str_starts_with($sourcePath, $prefix)) {
        web_install_fail('O arquivo de migracao precisa estar dentro do projeto.');
    }
    return $sourcePath;
}

function web_install_write_config(string $root, array $database): void
{
    $configDir = $root . '/config';
    if (!is_dir($configDir) && !mkdir($configDir, 0750, true) && !is_dir($configDir)) {
        web_install_fail('Nao foi possivel criar a pasta config.');
    }

    $configFile = $configDir . '/config.local.php';
    if (is_file($configFile)) {
        $backup = $configFile . '.before-install-' . date('YmdHis');
        if (!copy($configFile, $backup)) {
            web_install_fail('Nao foi possivel preservar o config.local.php atual.');
        }
    }

    $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn "
        . var_export(['database' => $database], true)
        . ";\n";
    if (file_put_contents($configFile, $content, LOCK_EX) === false) {
        web_install_fail('Nao foi possivel salvar config/config.local.php.');
    }
    @chmod($configFile, 0600);
}

function web_install_page(string $token, array $values, string $message = '', bool $success = false): never
{
    $statusClass = $success ? 'success' : 'error';
    $htmlMessage = $message !== '' ? '<div class="notice ' . $statusClass . '">' . nl2br(web_install_escape($message)) . '</div>' : '';
    $profile = (string)($values['profile'] ?? 'clt');
    $source = (string)($values['source'] ?? 'data/financeiro.sqlite');
    $sourceUserId = (string)($values['source_user_id'] ?? '');
    $migration = !empty($values['migrate']);
    $confirmText = (string)($values['confirm_text'] ?? '');
    $dbHost = (string)($values['db_host'] ?? 'sql110.byethost32.com');
    $dbPort = (string)($values['db_port'] ?? '3306');
    $dbName = (string)($values['db_name'] ?? 'b32_42890833_1');
    $dbUser = (string)($values['db_user'] ?? 'b32_42890833');
    ?>
    <!doctype html>
    <html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Instalação segura | Controle Financeiro</title>
        <style>
            :root { color-scheme: light; font-family: Inter, Segoe UI, Arial, sans-serif; background: #eef6f4; color: #153f46; }
            body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: linear-gradient(135deg, #eef8f5, #e5f0f5); }
            main { width: min(760px, 100%); background: #fff; border: 1px solid #cbe3df; border-radius: 22px; box-shadow: 0 18px 50px rgba(16, 75, 83, .14); overflow: hidden; }
            header { padding: 28px 32px; background: linear-gradient(135deg, #0b7f69, #1f9e87); color: #fff; }
            h1 { margin: 0 0 8px; font-size: 26px; } h2 { font-size: 17px; margin: 26px 0 12px; }
            header p { margin: 0; opacity: .9; } section { padding: 0 32px 32px; }
            .notice { margin: 22px 0 0; padding: 14px 16px; border-radius: 12px; white-space: normal; }
            .notice.success { background: #e5f8ef; border: 1px solid #8ed6b4; color: #0b684f; }
            .notice.error { background: #fff0f0; border: 1px solid #f1aaaa; color: #9a2929; }
            .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
            label { display: grid; gap: 6px; font-size: 13px; font-weight: 700; } input, select { width: 100%; box-sizing: border-box; padding: 12px 13px; border: 1px solid #bed8d4; border-radius: 10px; font: inherit; color: #153f46; background: #fbfefd; }
            .full { grid-column: 1 / -1; } .check { display: flex; align-items: center; gap: 9px; margin: 18px 0 10px; font-weight: 700; } .check input { width: auto; }
            .hint { color: #5c7678; font-size: 13px; line-height: 1.5; margin: 8px 0 0; }
            button { border: 0; border-radius: 11px; padding: 14px 20px; margin-top: 22px; background: #0b7f69; color: #fff; font: 800 14px inherit; cursor: pointer; box-shadow: 0 8px 16px rgba(11, 127, 105, .2); } button:hover { filter: brightness(1.08); }
            code { background: #edf6f5; padding: 2px 5px; border-radius: 5px; } @media (max-width: 620px) { .grid { grid-template-columns: 1fr; } .full { grid-column: auto; } section, header { padding-left: 20px; padding-right: 20px; } }
        </style>
    </head>
    <body>
    <main>
        <header><h1>Instalação do Controle Financeiro</h1><p>Configuração segura da hospedagem e do banco MySQL.</p></header>
        <section>
            <?= $htmlMessage ?>
            <form method="post" autocomplete="off">
                <input type="hidden" name="token" value="<?= web_install_escape($token) ?>">
                <h2>Banco MySQL/MariaDB</h2>
                <div class="grid">
                    <label>Host<input name="db_host" required value="<?= web_install_escape($dbHost) ?>"></label>
                    <label>Porta<input name="db_port" required inputmode="numeric" value="<?= web_install_escape($dbPort) ?>"></label>
                    <label>Nome do banco<input name="db_name" required value="<?= web_install_escape($dbName) ?>"></label>
                    <label>Usuário MySQL<input name="db_user" required value="<?= web_install_escape($dbUser) ?>"></label>
                    <label class="full">Senha MySQL<input name="db_password" type="password" required></label>
                </div>
                <p class="hint">Use a senha do usuário MySQL/vPanel, não a senha do login do sistema.</p>

                <h2>Migração opcional do sistema antigo</h2>
                <label class="check"><input type="checkbox" name="migrate" value="1" <?= $migration ? 'checked' : '' ?>> Mostrar/migrar dados do SQLite legado</label>
                <div class="grid">
                    <label>Arquivo SQLite dentro do projeto<input name="source" value="<?= web_install_escape($source) ?>"></label>
                    <label>CPF do usuário<input name="cpf" inputmode="numeric" value="<?= web_install_escape((string)($values['cpf'] ?? '')) ?>"></label>
                    <label>Perfil a aplicar<select name="profile"><option value="keep" <?= $profile === 'keep' ? 'selected' : '' ?>>Manter perfil</option><option value="clt" <?= $profile === 'clt' ? 'selected' : '' ?>>CLT</option><option value="pj" <?= $profile === 'pj' ? 'selected' : '' ?>>Prestador PJ</option><option value="comerciante_pf" <?= $profile === 'comerciante_pf' ? 'selected' : '' ?>>Comerciante PF</option><option value="comerciante_pj" <?= $profile === 'comerciante_pj' ? 'selected' : '' ?>>Comerciante PJ</option></select></label>
                    <label>ID da conta legada (se CPF duplicado)<input name="source_user_id" inputmode="numeric" value="<?= web_install_escape($sourceUserId) ?>"></label>
                    <label class="full">Confirmação para migrar<input name="confirm_text" placeholder="MIGRAR-DADOS-SEM-APAGAR" value="<?= web_install_escape($confirmText) ?>"></label>
                </div>
                <p class="hint">A migração é aditiva: cria tabelas ausentes e preserva registros existentes. Sem a confirmação exata, nenhum dado legado é importado.</p>
                <button type="submit">Testar conexão e instalar</button>
            </form>
        </section>
    </main>
    </body>
    </html>
    <?php
    exit;
}

if (is_file($lockFile)) {
    http_response_code(410);
    exit('Instalador ja utilizado. Remova install/install.lock somente por FTP se precisar reinstalar.');
}
if (!is_file($tokenFile)) {
    http_response_code(404);
    exit('Token do instalador ausente.');
}

$token = trim((string)file_get_contents($tokenFile));
$requestToken = (string)($_REQUEST['token'] ?? '');
if ($token === '' || $requestToken === '' || !hash_equals($token, $requestToken)) {
    http_response_code(403);
    exit('Token de instalacao invalido.');
}

$values = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : [];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    web_install_page($token, []);
}

try {
    $database = [
        'driver' => 'mysql',
        'host' => trim((string)($values['db_host'] ?? '')),
        'port' => max(1, (int)($values['db_port'] ?? 3306)),
        'database' => trim((string)($values['db_name'] ?? '')),
        'username' => trim((string)($values['db_user'] ?? '')),
        'password' => (string)($values['db_password'] ?? ''),
        'charset' => 'utf8mb4',
    ];
    foreach (['host', 'database', 'username', 'password'] as $required) {
        if ($database[$required] === '') {
            web_install_fail('Preencha todos os dados obrigatorios do MySQL.');
        }
    }
    if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
        web_install_fail('A extensao PDO MySQL nao esta habilitada nesta hospedagem.');
    }

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $database['host'], $database['port'], $database['database'], $database['charset']);
    $pdo = new PDO($dsn, $database['username'], $database['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => true,
    ]);
    require_once $root . '/api/bootstrap.php';
    sf_schema_mysql($pdo);

    web_install_write_config($root, $database);
    $message = 'Conexao MySQL validada e schema preparado sem apagar dados.';
    $migrate = !empty($values['migrate']);
    if ($migrate) {
        $cpf = trim((string)($values['cpf'] ?? ''));
        $profile = trim((string)($values['profile'] ?? 'keep'));
        $source = web_install_source_path($root, (string)($values['source'] ?? 'data/financeiro.sqlite'));
        $sourceUserIdValue = trim((string)($values['source_user_id'] ?? ''));
        $sourceUserId = $sourceUserIdValue === '' ? null : (int)$sourceUserIdValue;
        $confirm = (string)($values['confirm_text'] ?? '');
        require_once __DIR__ . '/legacy-migration.php';
        $migration = sf_legacy_migration_run($source, $cpf, $profile, true, $confirm, $sourceUserId);
        $inserted = [];
        foreach ($migration['inserted_by_table'] as $table => $count) {
            $inserted[] = $table . ': ' . $count;
        }
        $message .= "\nMigracao concluida sem apagar registros.\n" . ($inserted ? implode(', ', $inserted) : 'Nenhum registro novo foi necessario.');
    }

    $userCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    file_put_contents($lockFile, 'installed_at=' . date('c') . "\nusers=" . $userCount . "\n", LOCK_EX);
    @chmod($lockFile, 0600);
    web_install_page($token, [], $message . "\nUsuarios no banco: " . $userCount . ".\nRemova ou bloqueie este instalador apos concluir.", true);
} catch (Throwable $error) {
    web_install_page($token, $values, 'Falha: ' . $error->getMessage(), false);
}
