<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function pm_entity_config(string $entity): ?array
{
    $configs = [
        'clients' => [
            'table' => 'professional_clients',
            'order' => 'name COLLATE NOCASE ASC, id DESC',
            'fields' => [
                'name' => ['column' => 'name', 'type' => 'text', 'required' => true],
                'document' => ['column' => 'document', 'type' => 'text'],
                'phone' => ['column' => 'phone', 'type' => 'text'],
                'email' => ['column' => 'email', 'type' => 'text'],
                'notes' => ['column' => 'notes', 'type' => 'text'],
                'status' => ['column' => 'status', 'type' => 'status'],
            ],
        ],
        'catalog' => [
            'table' => 'professional_catalog',
            'order' => 'item_type ASC, name COLLATE NOCASE ASC, id DESC',
            'fields' => [
                'itemType' => ['column' => 'item_type', 'type' => 'catalog_type'],
                'name' => ['column' => 'name', 'type' => 'text', 'required' => true],
                'code' => ['column' => 'code', 'type' => 'text'],
                'price' => ['column' => 'price', 'type' => 'money'],
                'cost' => ['column' => 'cost', 'type' => 'money'],
                'stock' => ['column' => 'stock', 'type' => 'money'],
                'notes' => ['column' => 'notes', 'type' => 'text'],
            ],
        ],
        'cash' => [
            'table' => 'professional_cash_accounts',
            'order' => 'name COLLATE NOCASE ASC, id DESC',
            'fields' => [
                'name' => ['column' => 'name', 'type' => 'text', 'required' => true],
                'accountType' => ['column' => 'account_type', 'type' => 'account_type'],
                'balance' => ['column' => 'balance', 'type' => 'money'],
                'notes' => ['column' => 'notes', 'type' => 'text'],
            ],
        ],
        'categories' => [
            'table' => 'professional_categories',
            'order' => 'kind ASC, name COLLATE NOCASE ASC, id DESC',
            'fields' => [
                'name' => ['column' => 'name', 'type' => 'text', 'required' => true],
                'kind' => ['column' => 'kind', 'type' => 'category_kind'],
                'color' => ['column' => 'color', 'type' => 'color'],
            ],
        ],
        'payment_methods' => [
            'table' => 'professional_payment_methods',
            'order' => 'name COLLATE NOCASE ASC, id DESC',
            'fields' => [
                'name' => ['column' => 'name', 'type' => 'text', 'required' => true],
                'methodType' => ['column' => 'method_type', 'type' => 'method_type'],
            ],
        ],
        'fiscal_documents' => [
            'table' => 'professional_fiscal_documents',
            'order' => 'issue_date DESC, id DESC',
            'fields' => [
                'documentType' => ['column' => 'document_type', 'type' => 'document_type'],
                'documentNumber' => ['column' => 'document_number', 'type' => 'text'],
                'customerName' => ['column' => 'customer_name', 'type' => 'text'],
                'issueDate' => ['column' => 'issue_date', 'type' => 'date'],
                'amount' => ['column' => 'amount', 'type' => 'money'],
                'status' => ['column' => 'status', 'type' => 'document_status'],
                'notes' => ['column' => 'notes', 'type' => 'text'],
            ],
        ],
        'schedule' => [
            'table' => 'professional_schedule',
            'order' => 'due_date ASC, CASE WHEN due_time = \'\' THEN 1 ELSE 0 END, due_time ASC, id DESC',
            'fields' => [
                'title' => ['column' => 'title', 'type' => 'text', 'required' => true],
                'scheduleType' => ['column' => 'schedule_type', 'type' => 'schedule_type'],
                'dueDate' => ['column' => 'due_date', 'type' => 'date'],
                'dueTime' => ['column' => 'due_time', 'type' => 'time'],
                'amount' => ['column' => 'amount', 'type' => 'money'],
                'clientName' => ['column' => 'client_name', 'type' => 'text'],
                'status' => ['column' => 'status', 'type' => 'schedule_status'],
                'notes' => ['column' => 'notes', 'type' => 'text'],
            ],
        ],
    ];

    return $configs[$entity] ?? null;
}

function pm_normalize_field(string $type, $value)
{
    return match ($type) {
        'money' => max(0, sf_money($value)),
        'date' => sf_date($value),
        'time' => sf_time($value),
        'color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string)$value) ? (string)$value : '#1b9a68',
        'status' => in_array((string)$value, ['ativo', 'inativo'], true) ? (string)$value : 'ativo',
        'catalog_type' => in_array((string)$value, ['product', 'service'], true) ? (string)$value : 'product',
        'account_type' => in_array((string)$value, ['conta_corrente', 'caixa', 'pix', 'poupanca', 'outro'], true) ? (string)$value : 'conta_corrente',
        'category_kind' => in_array((string)$value, ['receita', 'despesa'], true) ? (string)$value : 'despesa',
        'method_type' => in_array((string)$value, ['pix', 'debito', 'credito', 'dinheiro', 'boleto', 'outro'], true) ? (string)$value : 'outro',
        'document_type' => in_array((string)$value, ['nfse', 'nfce', 'nota_entrada', 'outro'], true) ? (string)$value : 'nfse',
        'document_status' => in_array((string)$value, ['rascunho', 'emitido', 'cancelado'], true) ? (string)$value : 'emitido',
        'schedule_type' => in_array((string)$value, ['compromisso', 'vencimento', 'recebimento', 'tarefa'], true) ? (string)$value : 'compromisso',
        'schedule_status' => in_array((string)$value, ['agendado', 'concluido', 'cancelado'], true) ? (string)$value : 'agendado',
        default => sf_text($value ?? '', 800),
    };
}

function pm_entity_payload(string $entity, array $row): array
{
    return match ($entity) {
        'clients' => [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'document' => (string)($row['document'] ?? ''),
            'phone' => (string)($row['phone'] ?? ''),
            'email' => (string)($row['email'] ?? ''),
            'notes' => (string)($row['notes'] ?? ''),
            'status' => (string)($row['status'] ?? 'ativo'),
        ],
        'catalog' => [
            'id' => (int)$row['id'],
            'itemType' => (string)($row['item_type'] ?? 'product'),
            'name' => (string)$row['name'],
            'code' => (string)($row['code'] ?? ''),
            'price' => (float)($row['price'] ?? 0),
            'cost' => (float)($row['cost'] ?? 0),
            'stock' => (float)($row['stock'] ?? 0),
            'notes' => (string)($row['notes'] ?? ''),
        ],
        'cash' => [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'accountType' => (string)($row['account_type'] ?? 'conta_corrente'),
            'balance' => (float)($row['balance'] ?? 0),
            'notes' => (string)($row['notes'] ?? ''),
        ],
        'categories' => [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'kind' => (string)($row['kind'] ?? 'despesa'),
            'color' => (string)($row['color'] ?? '#1b9a68'),
        ],
        'payment_methods' => [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'methodType' => (string)($row['method_type'] ?? 'outro'),
        ],
        'fiscal_documents' => [
            'id' => (int)$row['id'],
            'documentType' => (string)($row['document_type'] ?? 'nfse'),
            'documentNumber' => (string)($row['document_number'] ?? ''),
            'customerName' => (string)($row['customer_name'] ?? ''),
            'issueDate' => (string)($row['issue_date'] ?? ''),
            'amount' => (float)($row['amount'] ?? 0),
            'status' => (string)($row['status'] ?? 'emitido'),
            'notes' => (string)($row['notes'] ?? ''),
        ],
        'schedule' => [
            'id' => (int)$row['id'],
            'title' => (string)$row['title'],
            'scheduleType' => (string)($row['schedule_type'] ?? 'compromisso'),
            'dueDate' => (string)($row['due_date'] ?? ''),
            'dueTime' => (string)($row['due_time'] ?? ''),
            'amount' => (float)($row['amount'] ?? 0),
            'clientName' => (string)($row['client_name'] ?? ''),
            'status' => (string)($row['status'] ?? 'agendado'),
            'notes' => (string)($row['notes'] ?? ''),
        ],
        default => [],
    };
}

function pm_list_entity(PDO $pdo, int $userId, string $entity): array
{
    $config = pm_entity_config($entity);
    if (!$config) {
        return [];
    }
    $stmt = $pdo->prepare("SELECT * FROM {$config['table']} WHERE user_id = :user_id ORDER BY {$config['order']}");
    $stmt->execute([':user_id' => $userId]);
    return array_map(fn(array $row): array => pm_entity_payload($entity, $row), $stmt->fetchAll());
}

function pm_singleton_payload(PDO $pdo, int $userId, string $entity): array
{
    $table = $entity === 'settings' ? 'professional_settings' : 'professional_permissions';
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([':user_id' => $userId]);
    $row = $stmt->fetch() ?: [];
    if ($entity === 'settings') {
        return [
            'legalName' => (string)($row['legal_name'] ?? ''),
            'tradeName' => (string)($row['trade_name'] ?? ''),
            'cnpj' => (string)($row['cnpj'] ?? ''),
            'phone' => (string)($row['phone'] ?? ''),
            'city' => (string)($row['city'] ?? ''),
            'notificationDays' => (int)($row['notification_days'] ?? 7),
        ];
    }
    return [
        'roleCode' => (string)($row['role_code'] ?? 'owner'),
        'canViewFinance' => (bool)($row['can_view_finance'] ?? true),
        'canManageClients' => (bool)($row['can_manage_clients'] ?? true),
        'canManageCatalog' => (bool)($row['can_manage_catalog'] ?? true),
        'canManageFiscal' => (bool)($row['can_manage_fiscal'] ?? true),
    ];
}

function pm_provider_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/professional-providers.php';
    }
    return is_array($config) ? $config : [];
}

function pm_provider_status(): array
{
    $config = pm_provider_config();
    $serpro = $config['serpro'] ?? [];
    $serasa = $config['serasa'] ?? [];
    $serasaAuth = (($serasa['token'] ?? '') !== '')
        || (($serasa['apiKey'] ?? '') !== '')
        || (($serasa['clientId'] ?? '') !== '' && ($serasa['clientSecret'] ?? '') !== '' && (($serasa['tokenUrl'] ?? '') !== '' || ($serasa['authMode'] ?? '') === 'basic'));
    return [
        'receita' => [
            'label' => 'Receita/Serpro',
            'configured' => ($serpro['clientId'] ?? '') !== '' && ($serpro['clientSecret'] ?? '') !== '' && ($serpro['userCpf'] ?? '') !== '',
            'scope' => 'CNPJ cadastral',
        ],
        'serasa' => [
            'label' => 'Serasa',
            'configured' => ($serasa['endpoint'] ?? '') !== '' && $serasaAuth,
            'scope' => 'CPF/CNPJ conforme contrato',
        ],
    ];
}

function pm_digits(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?: '';
}

function pm_valid_cpf(string $document): bool
{
    if (strlen($document) !== 11 || preg_match('/^(\d)\1{10}$/', $document)) return false;
    $sum = 0;
    for ($index = 0; $index < 9; $index++) $sum += (int)$document[$index] * (10 - $index);
    $digit = ($sum * 10) % 11;
    if ($digit === 10) $digit = 0;
    if ($digit !== (int)$document[9]) return false;
    $sum = 0;
    for ($index = 0; $index < 10; $index++) $sum += (int)$document[$index] * (11 - $index);
    $digit = ($sum * 10) % 11;
    if ($digit === 10) $digit = 0;
    return $digit === (int)$document[10];
}

function pm_valid_cnpj(string $document): bool
{
    if (strlen($document) !== 14 || preg_match('/^(\d)\1{13}$/', $document)) return false;
    $weights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    $sum = 0;
    for ($index = 0; $index < 12; $index++) $sum += (int)$document[$index] * $weights[$index];
    $remainder = $sum % 11;
    $digit = $remainder < 2 ? 0 : 11 - $remainder;
    if ($digit !== (int)$document[12]) return false;
    $weights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    $sum = 0;
    for ($index = 0; $index < 13; $index++) $sum += (int)$document[$index] * $weights[$index];
    $remainder = $sum % 11;
    $digit = $remainder < 2 ? 0 : 11 - $remainder;
    return $digit === (int)$document[13];
}

function pm_document_type(string $document): string
{
    return strlen($document) === 11 ? 'cpf' : 'cnpj';
}

function pm_mask_document(string $document): string
{
    return strlen($document) === 11
        ? substr($document, 0, 3) . '.***.***-' . substr($document, -2)
        : substr($document, 0, 2) . '.***.***/****-' . substr($document, -2);
}

function pm_http_json(string $url, string $method = 'GET', array $headers = [], ?array $payload = null, bool $formEncoded = false): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'status' => 0, 'message' => 'A extensão cURL do PHP não está habilitada.'];
    }
    $curl = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => $headers,
    ];
    if (defined('CURL_SSLVERSION_TLSv1_2')) $options[CURLOPT_SSLVERSION] = CURL_SSLVERSION_TLSv1_2;
    if ($payload !== null) {
        $options[CURLOPT_POSTFIELDS] = $formEncoded
            ? http_build_query($payload, '', '&', PHP_QUERY_RFC3986)
            : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    curl_setopt_array($curl, $options);
    $body = curl_exec($curl);
    $curlError = curl_error($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($body === false || $curlError !== '') {
        return ['ok' => false, 'status' => $status, 'message' => 'Não foi possível conectar ao provedor configurado.'];
    }
    $data = json_decode((string)$body, true);
    if (!is_array($data)) {
        return ['ok' => false, 'status' => $status, 'message' => 'O provedor retornou uma resposta inválida.'];
    }
    if ($status < 200 || $status >= 300) {
        return ['ok' => false, 'status' => $status, 'message' => 'O provedor recusou ou não concluiu a consulta.'];
    }
    return ['ok' => true, 'status' => $status, 'data' => $data];
}

function pm_external_key(string $value): string
{
    return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $value) ?: '');
}

function pm_external_value($payload, array $keys, int $depth = 0)
{
    if (!is_array($payload) || $depth > 5) return null;
    $normalizedKeys = array_map('pm_external_key', $keys);
    foreach ($payload as $key => $value) {
        if (in_array(pm_external_key((string)$key), $normalizedKeys, true)) {
            if (is_scalar($value) && trim((string)$value) !== '') return $value;
            if (is_array($value)) {
                $nested = pm_external_value($value, ['descricao', 'description', 'valor', 'value', 'nome', 'name', 'codigo', 'code'], $depth + 1);
                if ($nested !== null) return $nested;
            }
        }
    }
    foreach ($payload as $value) {
        $nested = pm_external_value($value, $keys, $depth + 1);
        if ($nested !== null) return $nested;
    }
    return null;
}

function pm_external_count($payload, array $keys): int
{
    $value = pm_external_value($payload, $keys);
    if (is_array($value)) return count($value);
    if ($value === null || $value === '' || $value === false) return 0;
    return is_numeric($value) ? max(0, (int)$value) : 1;
}

function pm_external_summary(array $payload, string $provider, string $document): array
{
    $status = pm_external_value($payload, ['situacaoCadastral', 'situacao', 'status', 'statusCadastral', 'situacaoEmpresa']);
    $openingDate = pm_external_value($payload, ['dataAbertura', 'dataFundacao', 'dataNascimento', 'openingDate', 'birthDate']);
    $name = pm_external_value($payload, ['nomeEmpresarial', 'razaoSocial', 'nomeCompleto', 'nome', 'name']);
    $tradeName = pm_external_value($payload, ['nomeFantasia', 'tradeName', 'fantasia']);
    $city = pm_external_value($payload, ['cidade', 'municipio', 'city']);
    $state = pm_external_value($payload, ['uf', 'estado', 'state']);
    $activity = pm_external_value($payload, ['cnaePrincipal', 'atividadePrincipal', 'mainActivity']);
    $score = pm_external_value($payload, ['score', 'scoreCredito', 'riscoCredito', 'creditScore']);
    $restrictionCount = pm_external_count($payload, ['negativacoes', 'restricoes', 'pendencias', 'pefin', 'refin', 'dividasVencidas', 'negativeRecords']);
    $protestCount = pm_external_count($payload, ['protestos', 'protests']);
    return [
        'provider' => $provider,
        'documentMasked' => pm_mask_document($document),
        'name' => $name !== null ? sf_text($name, 180) : '',
        'tradeName' => $tradeName !== null ? sf_text($tradeName, 180) : '',
        'status' => is_scalar($status) ? sf_text($status, 120) : '',
        'openingDate' => $openingDate !== null ? sf_text($openingDate, 30) : '',
        'city' => $city !== null ? sf_text($city, 120) : '',
        'state' => $state !== null ? sf_text($state, 60) : '',
        'mainActivity' => is_scalar($activity) ? sf_text($activity, 180) : '',
        'score' => $score !== null && is_numeric($score) ? (float)$score : null,
        'restrictionCount' => $restrictionCount,
        'protestCount' => $protestCount,
        'hasRestrictions' => $restrictionCount > 0 || $protestCount > 0,
        'disclaimer' => 'Resultado informativo para pré-análise. Não substitui análise de crédito, contrato ou decisão humana.',
    ];
}

function pm_serpro_lookup(string $document): array
{
    $config = pm_provider_config()['serpro'] ?? [];
    if (($config['clientId'] ?? '') === '' || ($config['clientSecret'] ?? '') === '' || ($config['userCpf'] ?? '') === '') {
        return ['ok' => false, 'code' => 'not_configured', 'message' => 'Receita/Serpro ainda não está configurada neste ambiente.'];
    }
    $token = pm_http_json(
        (string)$config['tokenUrl'],
        'POST',
        ['Authorization: Basic ' . base64_encode($config['clientId'] . ':' . $config['clientSecret']), 'Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
        ['grant_type' => 'client_credentials'],
        true
    );
    if (!$token['ok'] || empty($token['data']['access_token'])) {
        return ['ok' => false, 'code' => 'provider_error', 'message' => 'Não foi possível autenticar na Receita/Serpro.'];
    }
    $url = str_replace('{document}', $document, (string)$config['cnpjUrl']);
    $response = pm_http_json($url, 'GET', ['Authorization: Bearer ' . $token['data']['access_token'], 'x-cpf-usuario: ' . $config['userCpf'], 'Accept: application/json']);
    if (!$response['ok']) return ['ok' => false, 'code' => 'provider_error', 'message' => $response['message']];
    return ['ok' => true, 'summary' => pm_external_summary($response['data'], 'receita_serpro', $document)];
}

function pm_serasa_lookup(string $document): array
{
    $config = pm_provider_config()['serasa'] ?? [];
    if (($config['endpoint'] ?? '') === '') {
        return ['ok' => false, 'code' => 'not_configured', 'message' => 'Serasa ainda não está configurada neste ambiente.'];
    }
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    $authMode = (string)($config['authMode'] ?? 'bearer');
    if ($authMode === 'basic' && ($config['clientId'] ?? '') !== '' && ($config['clientSecret'] ?? '') !== '') {
        $headers[] = 'Authorization: Basic ' . base64_encode($config['clientId'] . ':' . $config['clientSecret']);
    } elseif ($authMode === 'x-api-key' && ($config['apiKey'] ?? '') !== '') {
        $headers[] = 'X-API-Key: ' . $config['apiKey'];
    } elseif (($config['token'] ?? '') !== '') {
        $headers[] = 'Authorization: Bearer ' . $config['token'];
    } elseif (($config['clientId'] ?? '') !== '' && ($config['clientSecret'] ?? '') !== '' && ($config['tokenUrl'] ?? '') !== '') {
        $token = pm_http_json(
            (string)$config['tokenUrl'],
            'POST',
            ['Authorization: Basic ' . base64_encode($config['clientId'] . ':' . $config['clientSecret']), 'Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
            ['grant_type' => 'client_credentials'],
            true
        );
        if (!$token['ok'] || empty($token['data']['access_token'])) return ['ok' => false, 'code' => 'provider_error', 'message' => 'Não foi possível autenticar na Serasa.'];
        $headers[] = 'Authorization: Bearer ' . $token['data']['access_token'];
    } else {
        return ['ok' => false, 'code' => 'not_configured', 'message' => 'A credencial da Serasa não foi configurada.'];
    }
    $documentField = preg_replace('/[^a-zA-Z0-9_]/', '', (string)($config['documentField'] ?? 'document')) ?: 'document';
    $payload = [$documentField => $document];
    if (($config['reportCode'] ?? '') !== '') $payload['reportCode'] = $config['reportCode'];
    $response = pm_http_json((string)$config['endpoint'], 'POST', $headers, $payload);
    if (!$response['ok']) return ['ok' => false, 'code' => 'provider_error', 'message' => $response['message']];
    return ['ok' => true, 'summary' => pm_external_summary($response['data'], 'serasa', $document)];
}

function pm_credit_check_payload(array $row): array
{
    $summary = json_decode((string)($row['summary_json'] ?? '{}'), true);
    return [
        'id' => (int)$row['id'],
        'clientId' => $row['client_id'] ? (int)$row['client_id'] : null,
        'documentType' => (string)$row['document_type'],
        'documentMasked' => (string)$row['document_masked'],
        'provider' => (string)$row['provider'],
        'purpose' => (string)$row['purpose'],
        'status' => (string)$row['status'],
        'results' => is_array($summary['results'] ?? null) ? $summary['results'] : [],
        'requestedAt' => (string)$row['requested_at'],
        'expiresAt' => (string)($row['expires_at'] ?? ''),
    ];
}

function pm_list_credit_checks(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT * FROM professional_credit_checks WHERE user_id = :user_id ORDER BY requested_at DESC, id DESC LIMIT 20');
    $stmt->execute([':user_id' => $userId]);
    return array_map('pm_credit_check_payload', $stmt->fetchAll());
}

function pm_run_credit_check(PDO $pdo, int $userId, array $input): array
{
    $document = pm_digits((string)($input['document'] ?? ''));
    $type = pm_document_type($document);
    if (($type === 'cpf' && !pm_valid_cpf($document)) || ($type === 'cnpj' && !pm_valid_cnpj($document))) {
        sf_json(['ok' => false, 'message' => 'Informe um CPF ou CNPJ válido para a consulta.'], 422);
    }
    if (empty($input['consent'])) {
        sf_json(['ok' => false, 'message' => 'Confirme que possui autorização ou base legal para consultar este documento.'], 422);
    }
    $provider = sf_text($input['provider'] ?? 'combined', 20);
    if (!in_array($provider, ['receita', 'serasa', 'combined'], true)) {
        sf_json(['ok' => false, 'message' => 'Fonte de consulta inválida.'], 422);
    }
    if ($type === 'cpf' && $provider === 'receita') {
        sf_json(['ok' => false, 'message' => 'A consulta Receita/Serpro desta tela é para CNPJ. Para CPF, use uma fonte de crédito contratada.'], 422);
    }
    $purpose = sf_text($input['purpose'] ?? 'analise_comercial', 40);
    if (!in_array($purpose, ['analise_comercial', 'venda_prazo', 'cadastro_cliente'], true)) $purpose = 'analise_comercial';
    $clientId = (int)($input['clientId'] ?? 0);
    if ($clientId > 0) {
        $clientStmt = $pdo->prepare('SELECT id FROM professional_clients WHERE id = :id AND user_id = :user_id LIMIT 1');
        $clientStmt->execute([':id' => $clientId, ':user_id' => $userId]);
        if (!$clientStmt->fetchColumn()) sf_json(['ok' => false, 'message' => 'Cliente não encontrado nesta conta.'], 422);
    }

    $sources = $provider === 'combined' ? ($type === 'cnpj' ? ['receita', 'serasa'] : ['serasa']) : [$provider];
    $results = [];
    foreach ($sources as $source) {
        $lookup = $source === 'receita' ? pm_serpro_lookup($document) : pm_serasa_lookup($document);
        $results[] = [
            'provider' => $source,
            'status' => $lookup['ok'] ? 'success' : (string)($lookup['code'] ?? 'provider_error'),
            'message' => $lookup['ok'] ? 'Consulta concluída.' : (string)$lookup['message'],
            'summary' => $lookup['ok'] ? $lookup['summary'] : null,
        ];
    }
    $successCount = count(array_filter($results, static fn(array $item): bool => $item['status'] === 'success'));
    $overallStatus = $successCount === count($results) ? 'success' : ($successCount > 0 ? 'partial' : 'not_configured');
    if ($successCount === 0 && count(array_filter($results, static fn(array $item): bool => $item['status'] === 'provider_error')) > 0) $overallStatus = 'error';
    $requestedAt = sf_now();
    $expiresAt = (new DateTimeImmutable($requestedAt))->modify('+30 days')->format('Y-m-d H:i:s');
    $summaryJson = json_encode(['results' => $results], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $stmt = $pdo->prepare('INSERT INTO professional_credit_checks (user_id, client_id, document_type, document_masked, provider, purpose, consented, status, summary_json, requested_at, expires_at, created_at) VALUES (:user_id, :client_id, :document_type, :document_masked, :provider, :purpose, 1, :status, :summary_json, :requested_at, :expires_at, :created_at)');
    $stmt->execute([
        ':user_id' => $userId,
        ':client_id' => $clientId > 0 ? $clientId : null,
        ':document_type' => $type,
        ':document_masked' => pm_mask_document($document),
        ':provider' => $provider,
        ':purpose' => $purpose,
        ':status' => $overallStatus,
        ':summary_json' => $summaryJson ?: '{}',
        ':requested_at' => $requestedAt,
        ':expires_at' => $expiresAt,
        ':created_at' => $requestedAt,
    ]);
    $checkId = (int)$pdo->lastInsertId();
    $rowStmt = $pdo->prepare('SELECT * FROM professional_credit_checks WHERE id = :id AND user_id = :user_id LIMIT 1');
    $rowStmt->execute([':id' => $checkId, ':user_id' => $userId]);
    $row = $rowStmt->fetch() ?: [];
    return [
        'ok' => true,
        'message' => $successCount > 0 ? 'Pré-análise concluída.' : 'Nenhum provedor está disponível; a consulta foi registrada para configuração posterior.',
        'check' => pm_credit_check_payload($row),
        'history' => pm_list_credit_checks($pdo, $userId),
    ];
}

function pm_analytics(PDO $pdo, int $userId, string $month): array
{
    $month = sf_month($month);
    $year = substr($month, 0, 4);

    $incomeStmt = $pdo->prepare('SELECT COALESCE(SUM(net_amount), 0) FROM incomes WHERE user_id = :user_id AND reference_month = :month');
    $incomeStmt->execute([':user_id' => $userId, ':month' => $month]);
    $revenue = (float)$incomeStmt->fetchColumn();

    $movementStmt = $pdo->prepare("SELECT
        COALESCE(SUM(CASE WHEN type = 'entrada' THEN amount ELSE 0 END), 0) AS revenue,
        COALESCE(SUM(CASE WHEN type <> 'entrada' THEN amount ELSE 0 END), 0) AS expenses,
        COALESCE(SUM(CASE WHEN type <> 'entrada' AND status <> 'pago' THEN amount ELSE 0 END), 0) AS pending
        FROM transactions WHERE user_id = :user_id AND reference_month = :month");
    $movementStmt->execute([':user_id' => $userId, ':month' => $month]);
    $movementTotals = $movementStmt->fetch() ?: [];
    $revenue += (float)($movementTotals['revenue'] ?? 0);
    $expenses = (float)($movementTotals['expenses'] ?? 0);

    $taxStmt = $pdo->prepare("SELECT
        COALESCE(SUM(amount), 0) AS total,
        COALESCE(SUM(CASE WHEN status = 'pago' THEN amount ELSE 0 END), 0) AS paid,
        COALESCE(SUM(CASE WHEN status <> 'pago' THEN amount ELSE 0 END), 0) AS pending
        FROM professional_taxes WHERE user_id = :user_id AND competence_month = :month");
    $taxStmt->execute([':user_id' => $userId, ':month' => $month]);
    $taxTotals = $taxStmt->fetch() ?: [];
    $taxTotal = (float)($taxTotals['total'] ?? 0);

    $scalar = static function (PDO $pdo, string $sql, array $params): float {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (float)$stmt->fetchColumn();
    };
    $cashBalance = $scalar($pdo, 'SELECT COALESCE(SUM(balance), 0) FROM professional_cash_accounts WHERE user_id = :user_id AND active = 1', [':user_id' => $userId]);
    $stockValue = $scalar($pdo, "SELECT COALESCE(SUM(cost * stock), 0) FROM professional_catalog WHERE user_id = :user_id AND active = 1 AND item_type = 'product'", [':user_id' => $userId]);
    $catalogValue = $scalar($pdo, "SELECT COALESCE(SUM(price * stock), 0) FROM professional_catalog WHERE user_id = :user_id AND active = 1 AND item_type = 'product'", [':user_id' => $userId]);
    $clientCount = (int)$scalar($pdo, "SELECT COUNT(*) FROM professional_clients WHERE user_id = :user_id AND status = 'ativo'", [':user_id' => $userId]);
    $catalogCount = (int)$scalar($pdo, 'SELECT COUNT(*) FROM professional_catalog WHERE user_id = :user_id AND active = 1', [':user_id' => $userId]);
    $scheduleOpen = (int)$scalar($pdo, "SELECT COUNT(*) FROM professional_schedule WHERE user_id = :user_id AND substr(due_date, 1, 7) = :month AND status = 'agendado'", [':user_id' => $userId, ':month' => $month]);

    $categoryStmt = $pdo->prepare("SELECT COALESCE(NULLIF(category, ''), 'Sem categoria') AS name, COALESCE(SUM(amount), 0) AS amount
        FROM transactions WHERE user_id = :user_id AND reference_month = :month AND type <> 'entrada'
        GROUP BY name ORDER BY amount DESC LIMIT 8");
    $categoryStmt->execute([':user_id' => $userId, ':month' => $month]);
    $topCategories = array_map(static fn(array $row): array => [
        'name' => (string)$row['name'],
        'amount' => round((float)$row['amount'], 2),
    ], $categoryStmt->fetchAll());

    $series = [];
    for ($number = 1; $number <= 12; $number++) {
        $reference = sprintf('%s-%02d', $year, $number);
        $series[$reference] = ['month' => $reference, 'revenue' => 0.0, 'expenses' => 0.0];
    }
    $seriesIncome = $pdo->prepare('SELECT reference_month, COALESCE(SUM(net_amount), 0) AS amount FROM incomes WHERE user_id = :user_id AND reference_month LIKE :year_prefix GROUP BY reference_month');
    $seriesIncome->execute([':user_id' => $userId, ':year_prefix' => $year . '-%']);
    foreach ($seriesIncome->fetchAll() as $row) {
        $reference = substr((string)$row['reference_month'], 0, 7);
        if (isset($series[$reference])) $series[$reference]['revenue'] += (float)$row['amount'];
    }
    $seriesMovements = $pdo->prepare("SELECT reference_month, type, COALESCE(SUM(amount), 0) AS amount FROM transactions WHERE user_id = :user_id AND reference_month LIKE :year_prefix GROUP BY reference_month, type");
    $seriesMovements->execute([':user_id' => $userId, ':year_prefix' => $year . '-%']);
    foreach ($seriesMovements->fetchAll() as $row) {
        $reference = substr((string)$row['reference_month'], 0, 7);
        if (!isset($series[$reference])) continue;
        if (($row['type'] ?? '') === 'entrada') $series[$reference]['revenue'] += (float)$row['amount'];
        else $series[$reference]['expenses'] += (float)$row['amount'];
    }

    $result = $revenue - $expenses - $taxTotal;
    return [
        'month' => $month,
        'revenue' => round($revenue, 2),
        'expenses' => round($expenses, 2),
        'pendingExpenses' => round((float)($movementTotals['pending'] ?? 0), 2),
        'taxTotal' => round($taxTotal, 2),
        'taxPaid' => round((float)($taxTotals['paid'] ?? 0), 2),
        'pendingTaxes' => round((float)($taxTotals['pending'] ?? 0), 2),
        'operationalResult' => round($result, 2),
        'marginPercent' => $revenue > 0 ? round(($result / $revenue) * 100, 1) : 0,
        'cashBalance' => round($cashBalance, 2),
        'stockValue' => round($stockValue, 2),
        'catalogValue' => round($catalogValue, 2),
        'clientCount' => $clientCount,
        'catalogCount' => $catalogCount,
        'scheduleOpen' => $scheduleOpen,
        'topCategories' => $topCategories,
        'monthlySeries' => array_values(array_map(static fn(array $item): array => [
            'month' => $item['month'],
            'revenue' => round($item['revenue'], 2),
            'expenses' => round($item['expenses'], 2),
        ], $series)),
    ];
}

function pm_snapshot(PDO $pdo, array $user, ?string $month = null): array
{
    $userId = (int)$user['id'];
    $selectedMonth = sf_month($month);
    $cardStmt = $pdo->prepare('SELECT id, nickname, institution, brand, last4, limit_total FROM cards WHERE user_id = :user_id AND active = 1 ORDER BY nickname COLLATE NOCASE');
    $cardStmt->execute([':user_id' => $userId]);
    $cards = array_map(static fn(array $row): array => [
        'id' => (int)$row['id'],
        'nickname' => (string)$row['nickname'],
        'institution' => (string)($row['institution'] ?? ''),
        'brand' => (string)($row['brand'] ?? ''),
        'last4' => (string)($row['last4'] ?? ''),
        'limitTotal' => (float)($row['limit_total'] ?? 0),
    ], $cardStmt->fetchAll());

    return [
        'ok' => true,
        'profileType' => sf_profile_type($user['profile_type'] ?? 'clt'),
        'clients' => pm_list_entity($pdo, $userId, 'clients'),
        'catalog' => pm_list_entity($pdo, $userId, 'catalog'),
        'cash' => pm_list_entity($pdo, $userId, 'cash'),
        'categories' => pm_list_entity($pdo, $userId, 'categories'),
        'paymentMethods' => pm_list_entity($pdo, $userId, 'payment_methods'),
        'fiscalDocuments' => pm_list_entity($pdo, $userId, 'fiscal_documents'),
        'schedule' => pm_list_entity($pdo, $userId, 'schedule'),
        'cards' => $cards,
        'settings' => pm_singleton_payload($pdo, $userId, 'settings'),
        'permissions' => pm_singleton_payload($pdo, $userId, 'permissions'),
        'analytics' => pm_analytics($pdo, $userId, $selectedMonth),
        'creditChecks' => pm_list_credit_checks($pdo, $userId),
        'providerStatus' => pm_provider_status(),
    ];
}

function pm_save_entity(PDO $pdo, int $userId, string $entity, array $record): array
{
    $config = pm_entity_config($entity);
    if (!$config) {
        sf_json(['ok' => false, 'message' => 'Módulo profissional inválido.'], 422);
    }

    $normalized = [];
    foreach ($config['fields'] as $inputName => $field) {
        $value = pm_normalize_field($field['type'], $record[$inputName] ?? '');
        if (!empty($field['required']) && trim((string)$value) === '') {
            sf_json(['ok' => false, 'message' => 'Preencha os campos obrigatórios do cadastro.'], 422);
        }
        $normalized[$field['column']] = $value;
    }

    $now = sf_now();
    $id = (int)($record['id'] ?? 0);
    if ($id > 0) {
        $sets = [];
        $params = [':id' => $id, ':user_id' => $userId, ':updated_at' => $now];
        foreach ($normalized as $column => $value) {
            $sets[] = "{$column} = :{$column}";
            $params[":{$column}"] = $value;
        }
        $stmt = $pdo->prepare("UPDATE {$config['table']} SET " . implode(', ', $sets) . ", updated_at = :updated_at WHERE id = :id AND user_id = :user_id");
        $stmt->execute($params);
    } else {
        $columns = array_merge(['user_id'], array_keys($normalized), ['created_at', 'updated_at']);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);
        $params = [':user_id' => $userId, ':created_at' => $now, ':updated_at' => $now];
        foreach ($normalized as $column => $value) {
            $params[":" . $column] = $value;
        }
        $stmt = $pdo->prepare("INSERT INTO {$config['table']} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")");
        $stmt->execute($params);
        $id = (int)$pdo->lastInsertId();
    }

    $stmt = $pdo->prepare("SELECT * FROM {$config['table']} WHERE id = :id AND user_id = :user_id LIMIT 1");
    $stmt->execute([':id' => $id, ':user_id' => $userId]);
    $row = $stmt->fetch();
    return $row ? pm_entity_payload($entity, $row) : [];
}

function pm_save_singleton(PDO $pdo, int $userId, string $entity, array $record): array
{
    if ($entity === 'settings') {
        $values = [
            'legal_name' => sf_text($record['legalName'] ?? '', 180),
            'trade_name' => sf_text($record['tradeName'] ?? '', 180),
            'cnpj' => sf_text($record['cnpj'] ?? '', 30),
            'phone' => sf_text($record['phone'] ?? '', 40),
            'city' => sf_text($record['city'] ?? '', 100),
            'notification_days' => max(0, min(90, (int)($record['notificationDays'] ?? 7))),
        ];
        $table = 'professional_settings';
    } elseif ($entity === 'permissions') {
        $values = [
            'role_code' => sf_text($record['roleCode'] ?? 'owner', 30),
            'can_view_finance' => !empty($record['canViewFinance']) ? 1 : 0,
            'can_manage_clients' => !empty($record['canManageClients']) ? 1 : 0,
            'can_manage_catalog' => !empty($record['canManageCatalog']) ? 1 : 0,
            'can_manage_fiscal' => !empty($record['canManageFiscal']) ? 1 : 0,
        ];
        $table = 'professional_permissions';
    } else {
        sf_json(['ok' => false, 'message' => 'Configuração profissional inválida.'], 422);
    }

    $now = sf_now();
    $existing = $pdo->prepare("SELECT id FROM {$table} WHERE user_id = :user_id LIMIT 1");
    $existing->execute([':user_id' => $userId]);
    $id = (int)($existing->fetchColumn() ?: 0);
    if ($id > 0) {
        $sets = [];
        $params = [':id' => $id, ':user_id' => $userId, ':updated_at' => $now];
        foreach ($values as $column => $value) {
            $sets[] = "{$column} = :{$column}";
            $params[":{$column}"] = $value;
        }
        $stmt = $pdo->prepare("UPDATE {$table} SET " . implode(', ', $sets) . ", updated_at = :updated_at WHERE id = :id AND user_id = :user_id");
        $stmt->execute($params);
    } else {
        $columns = array_merge(['user_id'], array_keys($values), ['updated_at']);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);
        $params = [':user_id' => $userId, ':updated_at' => $now];
        foreach ($values as $column => $value) {
            $params[":" . $column] = $value;
        }
        $stmt = $pdo->prepare("INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")");
        $stmt->execute($params);
    }
    return pm_singleton_payload($pdo, $userId, $entity);
}

function pm_search(PDO $pdo, int $userId, string $query): array
{
    $query = sf_text($query, 100);
    if ($query === '') {
        return ['clients' => [], 'catalog' => [], 'transactions' => []];
    }
    $like = '%' . $query . '%';
    $result = ['clients' => [], 'catalog' => [], 'transactions' => []];
    $stmt = $pdo->prepare('SELECT * FROM professional_clients WHERE user_id = :user_id AND (name LIKE :query OR document LIKE :query OR email LIKE :query) ORDER BY name COLLATE NOCASE LIMIT 30');
    $stmt->execute([':user_id' => $userId, ':query' => $like]);
    $result['clients'] = array_map(fn(array $row): array => pm_entity_payload('clients', $row), $stmt->fetchAll());
    $stmt = $pdo->prepare('SELECT * FROM professional_catalog WHERE user_id = :user_id AND (name LIKE :query OR code LIKE :query) ORDER BY name COLLATE NOCASE LIMIT 30');
    $stmt->execute([':user_id' => $userId, ':query' => $like]);
    $result['catalog'] = array_map(fn(array $row): array => pm_entity_payload('catalog', $row), $stmt->fetchAll());
    $stmt = $pdo->prepare('SELECT id, description, category, amount, movement_date, payment_method FROM transactions WHERE user_id = :user_id AND (description LIKE :query OR category LIKE :query) ORDER BY movement_date DESC, id DESC LIMIT 30');
    $stmt->execute([':user_id' => $userId, ':query' => $like]);
    $result['transactions'] = array_map(static fn(array $row): array => [
        'id' => (int)$row['id'],
        'description' => (string)$row['description'],
        'category' => (string)($row['category'] ?? ''),
        'amount' => (float)$row['amount'],
        'movementDate' => (string)$row['movement_date'],
        'paymentMethod' => (string)($row['payment_method'] ?? ''),
    ], $stmt->fetchAll());
    return $result;
}

try {
    $pdo = sf_db();
    $user = sf_require_user($pdo);
    if (sf_profile_type($user['profile_type'] ?? 'clt') === 'clt') {
        sf_json(['ok' => false, 'message' => 'Este módulo está disponível para perfis profissionais.'], 403);
    }
    $userId = (int)$user['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = sf_text($_GET['action'] ?? 'snapshot', 40);
        if ($action === 'search') {
            sf_json(['ok' => true, 'results' => pm_search($pdo, $userId, $_GET['q'] ?? '')]);
        }
        sf_json(pm_snapshot($pdo, $user, $_GET['month'] ?? null));
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sf_json(['ok' => false, 'message' => 'Método não permitido.'], 405);
    }

    $input = sf_input();
    $action = sf_text($input['action'] ?? '', 40);
    $entity = sf_text($input['entity'] ?? '', 40);

    if ($action === 'credit_precheck') {
        sf_json(pm_run_credit_check($pdo, $userId, $input));
    }

    if ($action === 'save') {
        $record = is_array($input['record'] ?? null) ? $input['record'] : [];
        $recordPayload = in_array($entity, ['settings', 'permissions'], true)
            ? pm_save_singleton($pdo, $userId, $entity, $record)
            : pm_save_entity($pdo, $userId, $entity, $record);
        sf_json([
            'ok' => true,
            'message' => 'Informação salva com sucesso.',
            'entity' => $entity,
            'record' => $recordPayload,
            'snapshot' => pm_snapshot($pdo, $user, $input['month'] ?? null),
        ]);
    }

    if ($action === 'delete') {
        $config = pm_entity_config($entity);
        if (!$config) {
            sf_json(['ok' => false, 'message' => 'Este cadastro não pode ser excluído.'], 422);
        }
        $stmt = $pdo->prepare("DELETE FROM {$config['table']} WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => (int)($input['id'] ?? 0), ':user_id' => $userId]);
        sf_json(['ok' => true, 'message' => 'Registro excluído.', 'snapshot' => pm_snapshot($pdo, $user, $input['month'] ?? null)]);
    }

    if ($action === 'snapshot') {
        sf_json(pm_snapshot($pdo, $user, $input['month'] ?? null));
    }

    sf_json(['ok' => false, 'message' => 'Ação profissional desconhecida.'], 422);
} catch (Throwable $error) {
    sf_json(['ok' => false, 'message' => 'Falha no módulo profissional: ' . $error->getMessage()], 500);
}
