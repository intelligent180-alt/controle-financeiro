<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

try {
    $pdo = sf_db();
    sf_json([
        'ok' => true,
        'app' => SF_APP_NAME,
        'version' => trim((string)@file_get_contents(__DIR__ . '/../VERSION')),
        'database' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
        'message' => 'Servidor e banco de dados disponiveis.',
    ]);
} catch (Throwable $error) {
    error_log('[Controle Financeiro] health: ' . $error->getMessage());
    sf_json([
        'ok' => false,
        'message' => 'O servidor respondeu, mas a conexao com o banco falhou. Verifique config/config.local.php e a extensao PDO do PHP.',
    ], 500);
}
