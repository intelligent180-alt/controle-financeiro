<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function sf_developer_key_scopes($value): string
{
    $allowed = ['read', 'write', 'all'];
    $requested = preg_split('/[\s,]+/', strtolower((string)$value)) ?: [];
    $scopes = array_values(array_intersect($allowed, array_map('trim', $requested)));
    return $scopes ? implode(',', array_unique($scopes)) : 'read';
}

try {
    $pdo = sf_db();
    $admin = sf_require_admin($pdo);
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    if ($method === 'GET') {
        $stmt = $pdo->prepare('SELECT id, name, key_prefix, scopes, active, rate_limit, last_used_at, expires_at, created_at, revoked_at FROM developer_api_keys WHERE user_id = :user_id ORDER BY id DESC');
        $stmt->execute([':user_id' => (int)$admin['id']]);
        sf_json(['ok' => true, 'keys' => $stmt->fetchAll()]);
    }

    if ($method === 'POST') {
        $input = sf_input();
        $name = sf_text($input['name'] ?? '', 120);
        if ($name === '') {
            sf_json(['ok' => false, 'message' => 'Informe um nome para identificar a integração.'], 422);
        }

        $expiresAt = '';
        if (trim((string)($input['expiresAt'] ?? '')) !== '') {
            $rawDate = trim((string)$input['expiresAt']);
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $rawDate);
            if (!$parsed || $parsed->format('Y-m-d') !== $rawDate) {
                sf_json(['ok' => false, 'message' => 'expiresAt deve usar o formato AAAA-MM-DD.'], 422);
            }
            $expiresAt = $parsed->setTime(23, 59, 59)->format('Y-m-d H:i:s');
        }

        $rateLimit = max(10, min(600, (int)($input['rateLimit'] ?? 120)));
        $token = 'sf_live_' . bin2hex(random_bytes(32));
        $now = sf_now();
        $stmt = $pdo->prepare('INSERT INTO developer_api_keys (user_id, name, key_prefix, key_hash, scopes, active, rate_limit, last_used_at, window_started_at, window_requests, expires_at, created_at, revoked_at) VALUES (:user_id, :name, :key_prefix, :key_hash, :scopes, 1, :rate_limit, NULL, NULL, 0, :expires_at, :created_at, NULL)');
        $stmt->execute([
            ':user_id' => (int)$admin['id'],
            ':name' => $name,
            ':key_prefix' => substr($token, 0, 20),
            ':key_hash' => hash('sha256', $token),
            ':scopes' => sf_developer_key_scopes($input['scopes'] ?? 'read'),
            ':rate_limit' => $rateLimit,
            ':expires_at' => $expiresAt !== '' ? $expiresAt : null,
            ':created_at' => $now,
        ]);
        sf_json([
            'ok' => true,
            'message' => 'Chave criada. Guarde o token agora; ele não será exibido novamente.',
            'key' => [
                'id' => (int)$pdo->lastInsertId(),
                'name' => $name,
                'token' => $token,
                'scopes' => sf_developer_key_scopes($input['scopes'] ?? 'read'),
                'rateLimit' => $rateLimit,
                'expiresAt' => $expiresAt,
            ],
        ], 201);
    }

    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            sf_json(['ok' => false, 'message' => 'Informe o id da chave.'], 422);
        }
        $stmt = $pdo->prepare('UPDATE developer_api_keys SET active = 0, revoked_at = :revoked_at WHERE id = :id AND user_id = :user_id');
        $stmt->execute([':revoked_at' => sf_now(), ':id' => $id, ':user_id' => (int)$admin['id']]);
        if ($stmt->rowCount() < 1) {
            sf_json(['ok' => false, 'message' => 'Chave não encontrada.'], 404);
        }
        sf_json(['ok' => true, 'message' => 'Chave revogada.']);
    }

    sf_json(['ok' => false, 'message' => 'Método não permitido.'], 405);
} catch (Throwable $error) {
    sf_json(['ok' => false, 'message' => 'Falha no gerenciamento de chaves: ' . $error->getMessage()], 500);
}
