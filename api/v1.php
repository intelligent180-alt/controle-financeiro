<?php

declare(strict_types=1);

/*
 * API REST pública versionada.
 *
 * O banco nunca é exposto ao cliente. A integração passa por este arquivo,
 * autenticada com Bearer token e limitada ao usuário dono da chave.
 */

require_once __DIR__ . '/bootstrap.php';

define('SF_FINANCE_LIBRARY_ONLY', true);
require_once __DIR__ . '/finance.php';

function sf_api_origin_allowed(): string
{
    $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
    $configured = trim((string)(getenv('SF_API_CORS_ORIGINS') ?: '*'));
    if ($configured === '*' || $origin === '') {
        return $configured === '*' ? '*' : '';
    }

    $allowed = array_filter(array_map('trim', explode(',', $configured)));
    return in_array($origin, $allowed, true) ? $origin : '';
}

function sf_api_json(array $payload, int $status = 200, array $headers = []): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    sf_headers();
    $origin = sf_api_origin_allowed();
    if ($origin !== '') {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }
    header('Access-Control-Allow-Headers: Authorization, Content-Type, X-API-Key');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Max-Age: 600');
    header('Cache-Control: no-store');
    foreach ($headers as $name => $value) {
        header($name . ': ' . $value);
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function sf_api_error(string $message, int $status = 400, string $code = 'invalid_request', array $details = []): void
{
    sf_api_json([
        'ok' => false,
        'error' => [
            'code' => $code,
            'message' => $message,
            'details' => $details,
        ],
        'requestId' => bin2hex(random_bytes(8)),
    ], $status);
}

function sf_api_headers(): array
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $normalized = [];
    foreach ($headers as $name => $value) {
        $normalized[strtolower((string)$name)] = trim((string)$value);
    }
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $normalized['authorization'] = trim((string)$_SERVER['HTTP_AUTHORIZATION']);
    }
    if (isset($_SERVER['HTTP_X_API_KEY'])) {
        $normalized['x-api-key'] = trim((string)$_SERVER['HTTP_X_API_KEY']);
    }
    return $normalized;
}

function sf_api_path(): array
{
    $requestPath = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/api/v1.php'));
    $relative = '';
    if ($script !== '' && str_starts_with($requestPath, $script)) {
        $relative = substr($requestPath, strlen($script));
    } elseif (!empty($_SERVER['PATH_INFO'])) {
        $relative = (string)$_SERVER['PATH_INFO'];
    }

    $segments = array_values(array_filter(explode('/', trim($relative, '/')), static fn($item) => $item !== ''));
    if (!$segments && !empty($_GET['resource'])) {
        $segments = array_values(array_filter(explode('/', trim((string)$_GET['resource'], '/')), static fn($item) => $item !== ''));
    }
    return array_map(static fn($item) => rawurldecode((string)$item), $segments);
}

function sf_api_scope_list(array $key): array
{
    $scopes = preg_split('/[\s,]+/', strtolower((string)($key['scopes'] ?? 'read'))) ?: [];
    return array_values(array_filter(array_map('trim', $scopes)));
}

function sf_api_scope_allowed(array $key, string $resource, string $operation): bool
{
    $scopes = sf_api_scope_list($key);
    if (in_array('all', $scopes, true) || in_array('admin', $scopes, true)) {
        return true;
    }

    $specific = $resource . ':' . $operation;
    if (in_array($specific, $scopes, true)) {
        return true;
    }
    if ($operation === 'read') {
        return in_array('read', $scopes, true) || in_array($resource . ':write', $scopes, true);
    }
    return in_array('write', $scopes, true);
}

function sf_api_bearer_token(): string
{
    $headers = sf_api_headers();
    $authorization = (string)($headers['authorization'] ?? '');
    if (preg_match('/^Bearer\s+(.+)$/i', $authorization, $match)) {
        return trim($match[1]);
    }
    return trim((string)($headers['x-api-key'] ?? ''));
}

function sf_api_authenticate(PDO $pdo): array
{
    $token = sf_api_bearer_token();
    if ($token === '' || strlen($token) < 24) {
        sf_api_error('Informe uma chave no header Authorization: Bearer <chave>.', 401, 'missing_api_key');
    }

    $stmt = $pdo->prepare('SELECT * FROM developer_api_keys WHERE key_hash = :key_hash AND active = 1 LIMIT 1');
    $stmt->execute([':key_hash' => hash('sha256', $token)]);
    $key = $stmt->fetch();
    if (!$key) {
        sf_api_error('Chave de API inválida ou revogada.', 401, 'invalid_api_key');
    }

    $expiresAt = trim((string)($key['expires_at'] ?? ''));
    if ($expiresAt !== '' && strtotime($expiresAt) !== false && strtotime($expiresAt) < time()) {
        sf_api_error('A chave de API expirou.', 401, 'expired_api_key');
    }

    $rateLimit = max(10, min(600, (int)($key['rate_limit'] ?? 120)));
    $windowStarted = trim((string)($key['window_started_at'] ?? ''));
    $windowTimestamp = $windowStarted !== '' ? strtotime($windowStarted) : false;
    $requestCount = (int)($key['window_requests'] ?? 0);
    $now = sf_now();
    if ($windowTimestamp === false || (time() - $windowTimestamp) >= 60) {
        $requestCount = 1;
        $update = $pdo->prepare('UPDATE developer_api_keys SET window_started_at = :window_started_at, window_requests = 1, last_used_at = :last_used_at WHERE id = :id');
        $update->execute([':window_started_at' => $now, ':last_used_at' => $now, ':id' => (int)$key['id']]);
    } else {
        if ($requestCount >= $rateLimit) {
            sf_api_error('Limite de requisições atingido. Tente novamente em alguns segundos.', 429, 'rate_limit_exceeded', ['limitPerMinute' => $rateLimit]);
        }
        $update = $pdo->prepare('UPDATE developer_api_keys SET window_requests = window_requests + 1, last_used_at = :last_used_at WHERE id = :id');
        $update->execute([':last_used_at' => $now, ':id' => (int)$key['id']]);
    }

    $userStmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute([':id' => (int)$key['user_id']]);
    $user = $userStmt->fetch();
    if (!$user) {
        sf_api_error('O usuário dono da chave não existe mais.', 401, 'owner_not_found');
    }

    return [$key, $user];
}

function sf_api_require_scope(array $key, string $resource, string $operation): void
{
    if (!sf_api_scope_allowed($key, $resource, $operation)) {
        sf_api_error('A chave não possui o escopo necessário para esta operação.', 403, 'insufficient_scope', [
            'required' => $resource . ':' . $operation,
        ]);
    }
}

function sf_api_month($value = null): string
{
    $month = sf_month($value);
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
        sf_api_error('Mês inválido. Use o formato AAAA-MM.', 422, 'invalid_month');
    }
    return $month;
}

function sf_api_transaction(PDO $pdo, int $userId, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT t.*, c.nickname AS card_nickname, c.last4 AS card_last4 FROM transactions t LEFT JOIN cards c ON c.id = t.card_id AND c.user_id = t.user_id WHERE t.id = :id AND t.user_id = :user_id LIMIT 1');
    $stmt->execute([':id' => $id, ':user_id' => $userId]);
    $row = $stmt->fetch();
    return $row ? sf_transaction_payload($row) : null;
}

function sf_api_card_from_input(PDO $pdo, int $userId, array &$transaction): void
{
    $cardId = (int)($transaction['cardId'] ?? 0);
    $last4 = substr(preg_replace('/\D+/', '', (string)($transaction['cardLast4'] ?? '')), -4);
    if ($cardId > 0) {
        $check = $pdo->prepare('SELECT id FROM cards WHERE id = :id AND user_id = :user_id AND active = 1 LIMIT 1');
        $check->execute([':id' => $cardId, ':user_id' => $userId]);
        if (!$check->fetchColumn()) {
            sf_api_error('Cartão não encontrado para este usuário.', 422, 'card_not_found');
        }
        return;
    }
    if ($last4 === '') {
        return;
    }
    $find = $pdo->prepare('SELECT id FROM cards WHERE user_id = :user_id AND last4 = :last4 AND active = 1 ORDER BY id LIMIT 1');
    $find->execute([':user_id' => $userId, ':last4' => $last4]);
    $matched = $find->fetchColumn();
    if (!$matched) {
        sf_api_error('Nenhum cartão ativo corresponde aos quatro últimos dígitos informados.', 422, 'card_not_found', ['last4' => $last4]);
    }
    $transaction['cardId'] = (int)$matched;
}

function sf_api_create_transaction(PDO $pdo, int $userId, array $input): array
{
    $transaction = is_array($input['transaction'] ?? null) ? $input['transaction'] : $input;
    $description = sf_text($transaction['description'] ?? '', 255);
    $amount = abs(sf_money($transaction['amount'] ?? 0));
    $movementDate = sf_date($transaction['movementDate'] ?? date('Y-m-d'));
    if ($description === '' || $amount <= 0) {
        sf_api_error('Informe description e amount maiores que zero.', 422, 'invalid_transaction');
    }

    $paymentMethod = strtolower(sf_text($transaction['paymentMethod'] ?? 'pix', 60));
    if ($paymentMethod === 'cartao' || $paymentMethod === 'cartão' || $paymentMethod === 'credit_card') {
        $paymentMethod = 'credito';
    }
    $transaction['paymentMethod'] = $paymentMethod;
    $transaction['movementDate'] = $movementDate;
    $transaction['amount'] = $amount;
    $transaction['type'] = sf_text($transaction['type'] ?? 'saida', 40);
    $transaction['status'] = sf_text($transaction['status'] ?? 'pendente', 40);
    $transaction['source'] = 'api_v1';
    $transaction['referenceMonth'] = sf_month($transaction['referenceMonth'] ?? substr($movementDate, 0, 7));
    $transaction['periodType'] = sf_period($transaction['periodType'] ?? 'dia20');
    $transaction['installmentCount'] = max(1, min(60, (int)($transaction['installmentCount'] ?? 1)));
    $transaction['installmentNumber'] = max(1, min($transaction['installmentCount'], (int)($transaction['installmentNumber'] ?? 1)));

    sf_api_card_from_input($pdo, $userId, $transaction);
    $ids = sf_save_transaction_installments($pdo, $userId, $transaction, true);
    $items = [];
    foreach ($ids as $id) {
        $item = sf_api_transaction($pdo, $userId, (int)$id);
        if ($item) {
            $items[] = $item;
        }
    }
    return $items;
}

function sf_api_create_card(PDO $pdo, int $userId, array $input, array $user): array
{
    $nickname = sf_text($input['nickname'] ?? '', 120);
    if ($nickname === '') {
        sf_api_error('Informe nickname para o cartão.', 422, 'invalid_card');
    }
    $number = preg_replace('/\D+/', '', (string)($input['number'] ?? $input['last4'] ?? ''));
    $last4 = substr($number, -4);
    $bin4 = strlen($number) >= 8 ? substr($number, 0, 4) : substr(preg_replace('/\D+/', '', (string)($input['bin4'] ?? '')), 0, 4);
    if (strlen($last4) !== 4) {
        sf_api_error('Informe last4 com quatro dígitos. Nunca envie o número completo do cartão.', 422, 'invalid_card');
    }

    $existing = $pdo->prepare('SELECT id FROM cards WHERE user_id = :user_id AND last4 = :last4 AND active = 1 LIMIT 1');
    $existing->execute([':user_id' => $userId, ':last4' => $last4]);
    if ($existing->fetchColumn()) {
        sf_api_error('Já existe um cartão ativo com esse final.', 409, 'card_already_exists');
    }

    $now = sf_now();
    $stmt = $pdo->prepare('INSERT INTO cards (user_id, nickname, institution, brand, bin4, last4, product_name, cardholder_name, expiry_date, closing_day, due_day, limit_total, active, notes, created_at, updated_at) VALUES (:user_id, :nickname, :institution, :brand, :bin4, :last4, :product_name, :cardholder_name, :expiry_date, :closing_day, :due_day, :limit_total, 1, :notes, :created_at, :updated_at)');
    $stmt->execute([
        ':user_id' => $userId,
        ':nickname' => $nickname,
        ':institution' => sf_text($input['institution'] ?? '', 120),
        ':brand' => sf_text($input['brand'] ?? sf_card_brand_from_bin4($bin4), 40),
        ':bin4' => $bin4,
        ':last4' => $last4,
        ':product_name' => sf_text($input['productName'] ?? '', 120),
        ':cardholder_name' => sf_text($input['cardholderName'] ?? ($user['name'] ?? ''), 160),
        ':expiry_date' => sf_card_expiry((string)($input['expiryDate'] ?? '')),
        ':closing_day' => max(1, min(31, (int)($input['closingDay'] ?? 1))),
        ':due_day' => max(1, min(31, (int)($input['dueDay'] ?? 10))),
        ':limit_total' => abs(sf_money($input['limitTotal'] ?? 0)),
        ':notes' => sf_text($input['notes'] ?? '', 800),
        ':created_at' => $now,
        ':updated_at' => $now,
    ]);
    $cardId = (int)$pdo->lastInsertId();
    $read = $pdo->prepare('SELECT * FROM cards WHERE id = :id AND user_id = :user_id LIMIT 1');
    $read->execute([':id' => $cardId, ':user_id' => $userId]);
    return sf_card_payload($read->fetch() ?: []);
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        sf_api_json(['ok' => true, 'message' => 'Preflight autorizado.']);
    }

    $pdo = sf_db();
    $segments = sf_api_path();
    $resource = strtolower((string)($segments[0] ?? ''));
    $id = isset($segments[1]) && ctype_digit((string)$segments[1]) ? (int)$segments[1] : 0;
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    if ($resource === '' || $resource === 'docs') {
        sf_api_json([
            'ok' => true,
            'name' => SF_APP_NAME . ' REST API',
            'version' => '1.0.0',
            'baseUrl' => '/api/v1.php',
            'documentation' => '/docs/api-rest.md',
            'openapi' => '/api/v1-openapi.json',
            'authentication' => 'Bearer API key',
        ]);
    }

    if ($resource === 'health') {
        sf_api_json(['ok' => true, 'data' => ['service' => 'online', 'version' => '1.0.0', 'database' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)]]);
    }

    [$key, $user] = sf_api_authenticate($pdo);
    $userId = (int)$user['id'];

    if ($resource === 'me') {
        sf_api_require_scope($key, 'me', 'read');
        sf_api_json(['ok' => true, 'data' => sf_user_payload($user), 'meta' => ['scopes' => sf_api_scope_list($key)]]);
    }

    if ($resource === 'dashboard' && $method === 'GET') {
        sf_api_require_scope($key, 'dashboard', 'read');
        $month = sf_api_month($_GET['month'] ?? null);
        sf_api_json(['ok' => true, 'data' => sf_dashboard($pdo, $user, $month), 'meta' => ['month' => $month]]);
    }

    if ($resource === 'transactions') {
        if ($method === 'GET') {
            sf_api_require_scope($key, 'transactions', 'read');
            if ($id > 0) {
                $item = sf_api_transaction($pdo, $userId, $id);
                if (!$item) sf_api_error('Movimentação não encontrada.', 404, 'not_found');
                sf_api_json(['ok' => true, 'data' => $item]);
            }
            $month = sf_api_month($_GET['month'] ?? null);
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
            $offset = max(0, (int)($_GET['offset'] ?? 0));
            $stmt = $pdo->prepare('SELECT t.*, c.nickname AS card_nickname, c.last4 AS card_last4 FROM transactions t LEFT JOIN cards c ON c.id = t.card_id AND c.user_id = t.user_id WHERE t.user_id = :user_id AND t.reference_month = :reference_month ORDER BY t.movement_date DESC, t.id DESC LIMIT :limit OFFSET :offset');
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':reference_month', $month, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $items = array_map('sf_transaction_payload', $stmt->fetchAll());
            sf_api_json(['ok' => true, 'data' => $items, 'meta' => ['month' => $month, 'limit' => $limit, 'offset' => $offset, 'count' => count($items)]]);
        }

        if ($method === 'POST') {
            sf_api_require_scope($key, 'transactions', 'write');
            $items = sf_api_create_transaction($pdo, $userId, sf_input());
            sf_api_json(['ok' => true, 'data' => count($items) === 1 ? $items[0] : $items, 'meta' => ['created' => count($items)]], 201);
        }

        if ($method === 'DELETE' && $id > 0) {
            sf_api_require_scope($key, 'transactions', 'write');
            $stmt = $pdo->prepare('DELETE FROM transactions WHERE id = :id AND user_id = :user_id');
            $stmt->execute([':id' => $id, ':user_id' => $userId]);
            if ($stmt->rowCount() < 1) sf_api_error('Movimentação não encontrada.', 404, 'not_found');
            sf_api_json(['ok' => true, 'data' => ['id' => $id, 'deleted' => true]]);
        }
    }

    if ($resource === 'cards') {
        if ($method === 'GET') {
            sf_api_require_scope($key, 'cards', 'read');
            sf_api_json(['ok' => true, 'data' => sf_list_cards($pdo, $userId)]);
        }
        if ($method === 'POST') {
            sf_api_require_scope($key, 'cards', 'write');
            sf_api_json(['ok' => true, 'data' => sf_api_create_card($pdo, $userId, sf_input(), $user)], 201);
        }
    }

    if ($resource === 'incomes' && $method === 'GET') {
        sf_api_require_scope($key, 'incomes', 'read');
        $month = sf_api_month($_GET['month'] ?? null);
        sf_api_json(['ok' => true, 'data' => sf_list_incomes($pdo, $userId, $month), 'meta' => ['month' => $month]]);
    }

    sf_api_error('Rota ou método não encontrado.', 404, 'route_not_found');
} catch (Throwable $error) {
    error_log('[Controle Financeiro] public API: ' . $error->getMessage());
    sf_api_error('Falha interna da API.', 500, 'internal_error');
}
