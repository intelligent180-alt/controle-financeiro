<?php

declare(strict_types=1);

/* Backup temporario do usuario com maior volume de movimentacoes. */

$root = dirname(__DIR__);
$tokenFile = __DIR__ . '/backup.token';
$lockFile = __DIR__ . '/backup.lock';

function backup_web_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function backup_web_identifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function backup_web_tables(PDO $pdo): array
{
    $tables = array_map('strval', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN));
    usort($tables, static function (string $left, string $right): int {
        $priority = [
            'users' => 0,
            'cards' => 10,
            'imports' => 20,
            'incomes' => 30,
            'goals' => 40,
            'professional_clients' => 50,
            'professional_catalog' => 60,
            'professional_cash_accounts' => 70,
            'professional_categories' => 80,
            'professional_payment_methods' => 90,
            'professional_settings' => 100,
            'professional_permissions' => 110,
            'professional_credit_checks' => 120,
            'transactions' => 200,
        ];
        return ($priority[$left] ?? 500) <=> ($priority[$right] ?? 500);
    });
    return $tables;
}

function backup_web_user_ranking(PDO $pdo): array
{
    $tables = backup_web_tables($pdo);
    $scopedTables = [];
    foreach ($tables as $table) {
        $columns = array_map('strval', $pdo->query('SHOW COLUMNS FROM ' . backup_web_identifier($table))->fetchAll(PDO::FETCH_COLUMN));
        if ($table !== 'users' && in_array('user_id', $columns, true)) {
            $scopedTables[] = $table;
        }
    }

    $users = $pdo->query('SELECT id, name, cpf, username, profile_type FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as &$user) {
        $user['id'] = (int)$user['id'];
        $user['rows_by_table'] = [];
        $user['total_rows'] = 0;
        foreach ($scopedTables as $table) {
            $statement = $pdo->prepare(
                'SELECT COUNT(*) FROM ' . backup_web_identifier($table) . ' WHERE user_id = :user_id'
            );
            $statement->execute([':user_id' => $user['id']]);
            $count = (int)$statement->fetchColumn();
            $user['rows_by_table'][$table] = $count;
            $user['total_rows'] += $count;
        }
        $user['movement_rows'] = (int)($user['rows_by_table']['transactions'] ?? 0);
    }
    unset($user);

    usort($users, static function (array $left, array $right): int {
        return [$right['movement_rows'], $right['total_rows'], $left['id']]
            <=> [$left['movement_rows'], $left['total_rows'], $right['id']];
    });
    return [$users, $tables];
}

function backup_web_dump_user(PDO $pdo, int $userId, array $tables, string $output): array
{
    $directory = dirname($output);
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Nao foi possivel criar a pasta do backup.');
    }
    $handle = fopen($output, 'wb');
    if ($handle === false) {
        throw new RuntimeException('Nao foi possivel criar o dump do usuario.');
    }

    fwrite($handle, "-- Backup aditivo de um usuario do Sistema Financeiro\n");
    fwrite($handle, "-- Nenhum DROP, DELETE ou TRUNCATE foi executado.\n");
    fwrite($handle, '-- Criado em ' . date('c') . "\n\n");
    fwrite($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

    $rowsByTable = [];
    foreach ($tables as $table) {
        $quoted = backup_web_identifier($table);
        $create = $pdo->query('SHOW CREATE TABLE ' . $quoted)->fetch(PDO::FETCH_NUM);
        $createSql = (string)($create[1] ?? '');
        $createSql = preg_replace('/^CREATE TABLE /i', 'CREATE TABLE IF NOT EXISTS ', $createSql) ?: $createSql;
        fwrite($handle, $createSql . ";\n\n");

        if ($table === 'users') {
            $statement = $pdo->prepare('SELECT * FROM ' . $quoted . ' WHERE id = :id');
            $statement->execute([':id' => $userId]);
        } else {
            $columns = array_map('strval', $pdo->query('SHOW COLUMNS FROM ' . $quoted)->fetchAll(PDO::FETCH_COLUMN));
            if (!in_array('user_id', $columns, true)) {
                continue;
            }
            $statement = $pdo->prepare('SELECT * FROM ' . $quoted . ' WHERE user_id = :user_id ORDER BY id');
            $statement->execute([':user_id' => $userId]);
        }

        $count = 0;
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $columnSql = implode(', ', array_map('backup_web_identifier', array_keys($row)));
            $values = [];
            foreach ($row as $value) {
                $values[] = $value === null ? 'NULL' : $pdo->quote((string)$value);
            }
            fwrite(
                $handle,
                'INSERT IGNORE INTO ' . $quoted . ' (' . $columnSql . ') VALUES ('
                . implode(', ', $values) . ");\n"
            );
            $count++;
        }
        $rowsByTable[$table] = $count;
        fwrite($handle, "\n");
    }

    fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($handle);
    return $rowsByTable;
}

function backup_web_page(string $token, array $users, string $message = '', bool $success = false): never
{
    $notice = $message !== ''
        ? '<div class="notice ' . ($success ? 'success' : 'error') . '">' . nl2br(backup_web_escape($message)) . '</div>'
        : '';
    ?>
    <!doctype html>
    <html lang="pt-BR">
    <head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Backup por usuário | Controle Financeiro</title>
    <style>
    :root{font-family:Inter,Segoe UI,Arial,sans-serif;color:#173f46;background:#eef6f4}body{margin:0;padding:24px;background:linear-gradient(135deg,#eef8f5,#e5f0f5)}main{max-width:980px;margin:auto;background:#fff;border:1px solid #cbe3df;border-radius:20px;box-shadow:0 18px 50px #104b5324;overflow:hidden}header{padding:26px 30px;color:#fff;background:linear-gradient(135deg,#0b7f69,#1f9e87)}h1{margin:0 0 6px;font-size:25px}header p{margin:0;opacity:.9}section{padding:24px 30px}.notice{padding:14px 16px;border-radius:12px;margin-bottom:18px;white-space:normal}.success{background:#e5f8ef;border:1px solid #8ed6b4;color:#0b684f}.error{background:#fff0f0;border:1px solid #f1aaaa;color:#9a2929}.table{overflow:auto;border:1px solid #d6e8e5;border-radius:12px}table{width:100%;border-collapse:collapse;min-width:700px}th,td{text-align:left;padding:12px;border-bottom:1px solid #e6f0ee;font-size:13px}th{background:#f1faf8;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.best{background:#e9faf0}.badge{display:inline-block;padding:4px 8px;border-radius:999px;background:#0b7f69;color:#fff;font-size:11px;font-weight:800}button{border:0;border-radius:10px;padding:13px 18px;background:#0b7f69;color:#fff;font-weight:800;cursor:pointer;margin-top:18px}.hint{font-size:13px;color:#5c7678;line-height:1.5}
    </style></head>
    <body><main><header><h1>Backup do usuário mais ativo</h1><p>Ranking por movimentações e registros financeiros no MySQL hospedado.</p></header><section>
    <?= $notice ?>
    <p class="hint">O backup é criado no servidor em formato SQL aditivo. Nenhum dado original é alterado.</p>
    <div class="table"><table><thead><tr><th>Usuário</th><th>Perfil</th><th>Movimentações</th><th>Registros relacionados</th><th>ID</th><th></th></tr></thead><tbody>
    <?php foreach ($users as $index => $user): ?>
        <tr class="<?= $index === 0 ? 'best' : '' ?>"><td><?= backup_web_escape((string)($user['name'] ?? '')) ?></td><td><?= backup_web_escape((string)($user['profile_type'] ?? '')) ?></td><td><?= (int)$user['movement_rows'] ?></td><td><?= (int)$user['total_rows'] ?></td><td><?= (int)$user['id'] ?></td><td><?= $index === 0 ? '<span class="badge">MAIOR MOVIMENTO</span>' : '' ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <?php if (!$success): ?><form method="post"><input type="hidden" name="token" value="<?= backup_web_escape($token) ?>"><input type="hidden" name="action" value="backup"><button type="submit">Gerar backup do primeiro usuário</button></form><?php endif; ?>
    </section></main></body></html>
    <?php
    exit;
}

if (is_file($lockFile)) {
    http_response_code(410);
    exit('Ferramenta de backup ja utilizada.');
}
if (!is_file($tokenFile)) {
    http_response_code(404);
    exit('Token de backup ausente.');
}
$token = trim((string)file_get_contents($tokenFile));
$requestToken = (string)($_REQUEST['token'] ?? '');
if ($token === '' || $requestToken === '' || !hash_equals($token, $requestToken)) {
    http_response_code(403);
    exit('Token de backup invalido.');
}

try {
    require_once $root . '/api/bootstrap.php';
    $pdo = sf_db();
    if (sf_database_config()['driver'] !== 'mysql') {
        throw new RuntimeException('O backup do usuario exige o banco MySQL hospedado.');
    }
    [$users, $tables] = backup_web_user_ranking($pdo);
    if ($users === []) {
        throw new RuntimeException('Nenhum usuario encontrado no MySQL.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'backup') {
        $selected = $users[0];
        $folder = $root . '/backup/user-most-movements-' . date('Ymd-His');
        $dump = $folder . '/mysql-user-backup.sql';
        $rows = backup_web_dump_user($pdo, (int)$selected['id'], $tables, $dump);
        $manifest = [
            'created_at' => date('c'),
            'source' => 'mysql hospedado',
            'selection' => 'maior quantidade de transactions; desempate por registros totais e menor id',
            'user_id' => (int)$selected['id'],
            'name' => (string)$selected['name'],
            'cpf' => (string)($selected['cpf'] ?? ''),
            'profile_type' => (string)($selected['profile_type'] ?? ''),
            'movement_rows' => (int)$selected['movement_rows'],
            'total_rows' => (int)$selected['total_rows'],
            'rows_by_table' => $rows,
            'note' => 'Backup aditivo; nenhum registro original foi alterado.',
        ];
        file_put_contents($folder . '/backup-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, LOCK_EX);
        file_put_contents($lockFile, 'created_at=' . date('c') . "\nuser_id=" . (int)$selected['id'] . "\n", LOCK_EX);
        @unlink($tokenFile);
        backup_web_page($token, $users, "Backup criado com sucesso.\nUsuario: {$selected['name']} (ID {$selected['id']})\nMovimentacoes: {$selected['movement_rows']}\nArquivo: " . str_replace($root . '/', '', $dump), true);
    }
    backup_web_page($token, $users);
} catch (Throwable $error) {
    backup_web_page($token, [], 'Falha: ' . $error->getMessage());
}
