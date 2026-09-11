<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Patch disponivel somente pelo terminal.\n");
}

require_once __DIR__ . '/../api/bootstrap.php';

$options = getopt('', ['reset-all', 'confirm:', 'admin-password:']);
if (!isset($options['reset-all']) || ($options['confirm'] ?? '') !== 'RESET-TODOS-DADOS') {
    fwrite(STDERR, "Uso: php install/patch-20260911.php --reset-all --confirm=RESET-TODOS-DADOS --admin-password=SENHA\n");
    exit(2);
}

$adminPassword = (string)($options['admin-password'] ?? '');
if (strlen($adminPassword) < 3) {
    fwrite(STDERR, "Informe uma senha inicial com pelo menos 3 caracteres.\n");
    exit(2);
}

$pdo = sf_db();
$tables = [
    'transactions',
    'incomes',
    'cards',
    'goals',
    'imports',
    'bank_connections',
    'professional_assets',
    'professional_taxes',
    'professional_clients',
    'professional_catalog',
    'professional_cash_accounts',
    'professional_categories',
    'professional_payment_methods',
    'professional_fiscal_documents',
    'professional_settings',
    'professional_permissions',
    'professional_schedule',
    'professional_credit_checks',
    'users',
];

$now = sf_now();
$pdo->beginTransaction();
try {
    foreach ($tables as $table) {
        $pdo->exec("DELETE FROM {$table}");
    }

    $stmt = $pdo->prepare('
        INSERT INTO users (name, email, cpf, username, profile_type, profile_completed, profile_answers_json, password_hash, is_admin, must_change_password, created_at, updated_at)
        VALUES (:name, :email, :cpf, :username, :profile_type, 1, :profile_answers_json, :password_hash, 1, 1, :created_at, :updated_at)
    ');
    $stmt->execute([
        ':name' => 'Administrador',
        ':email' => 'admin@controle-financeiro.local',
        ':cpf' => '',
        ':username' => 'admin',
        ':profile_type' => 'clt',
        ':profile_answers_json' => '{}',
        ':password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
        ':created_at' => $now,
        ':updated_at' => $now,
    ]);
    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Falha no patch: {$error->getMessage()}\n");
    exit(1);
}

fwrite(STDOUT, "Dados de todos os usuarios zerados. Usuario inicial criado: admin.\n");
fwrite(STDOUT, "A senha inicial exige troca no primeiro acesso.\n");
