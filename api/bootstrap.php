<?php

declare(strict_types=1);

const SF_APP_NAME = 'Sistema Financeiro';
const SF_DB_FILE = __DIR__ . '/../data/financeiro.sql';
const SF_DEFAULT_USERNAME = 'admin';
const SF_DEFAULT_EMAIL = 'admin@controle-financeiro.local';
const SF_DEFAULT_CPF = '';
const SF_DEFAULT_PASSWORD = 'admin';

function sf_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

function sf_json(array $payload, int $status = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    sf_headers();
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function sf_input(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function sf_now(): string
{
    return date('Y-m-d H:i:s');
}

function sf_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('SF_AUTH');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function sf_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dir = dirname(SF_DB_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $pdo = new PDO('sqlite:' . SF_DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    sf_schema($pdo);
    sf_default_user($pdo);

    return $pdo;
}

function sf_schema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            cpf TEXT DEFAULT '',
            username TEXT DEFAULT '',
            profile_type TEXT NOT NULL DEFAULT 'clt',
            profile_completed INTEGER NOT NULL DEFAULT 1,
            profile_answers_json TEXT DEFAULT '{}',
            password_hash TEXT NOT NULL,
            is_admin INTEGER NOT NULL DEFAULT 0,
            must_change_password INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )
    ");

    $userColumns = $pdo->query('PRAGMA table_info(users)')->fetchAll();
    $userColumnNames = array_column($userColumns, 'name');
    if (!in_array('profile_type', $userColumnNames, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_type TEXT NOT NULL DEFAULT 'clt'");
    }
    if (!in_array('profile_completed', $userColumnNames, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_completed INTEGER NOT NULL DEFAULT 1");
    }
    if (!in_array('profile_answers_json', $userColumnNames, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_answers_json TEXT DEFAULT '{}'");
    }
    if (!in_array('username', $userColumnNames, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN username TEXT DEFAULT ''");
    }
    if (!in_array('is_admin', $userColumnNames, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN is_admin INTEGER NOT NULL DEFAULT 0');
    }
    if (!in_array('must_change_password', $userColumnNames, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN must_change_password INTEGER NOT NULL DEFAULT 0');
    }
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_users_username ON users (username) WHERE username <> ''");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            type TEXT NOT NULL DEFAULT 'saida',
            description TEXT NOT NULL,
            category TEXT DEFAULT '',
            amount REAL NOT NULL DEFAULT 0,
            installment_count INTEGER NOT NULL DEFAULT 1,
            installment_number INTEGER NOT NULL DEFAULT 1,
            installment_amount REAL NOT NULL DEFAULT 0,
            installment_group TEXT DEFAULT '',
            movement_date TEXT NOT NULL,
            movement_time TEXT DEFAULT '',
            payment_method TEXT DEFAULT 'pix',
            period_type TEXT NOT NULL DEFAULT 'dia20',
            reference_month TEXT NOT NULL,
            period_due_date TEXT DEFAULT '',
            card_id INTEGER DEFAULT NULL,
            source TEXT DEFAULT 'manual',
            status TEXT DEFAULT 'pago',
            notes TEXT DEFAULT '',
            import_id INTEGER DEFAULT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // Migracao segura para bancos criados antes do registro de horario.
    $transactionColumns = $pdo->query('PRAGMA table_info(transactions)')->fetchAll();
    $hasMovementTime = false;
    foreach ($transactionColumns as $column) {
        if (($column['name'] ?? '') === 'movement_time') {
            $hasMovementTime = true;
            break;
        }
    }
    if (!$hasMovementTime) {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN movement_time TEXT DEFAULT ''");
    }
    $transactionColumns = $pdo->query('PRAGMA table_info(transactions)')->fetchAll();
    $transactionColumnNames = array_column($transactionColumns, 'name');
    $legacyColumns = [
        'installment_count' => 'INTEGER NOT NULL DEFAULT 1',
        'installment_number' => 'INTEGER NOT NULL DEFAULT 1',
        'installment_amount' => 'REAL NOT NULL DEFAULT 0',
        'installment_group' => "TEXT DEFAULT ''",
    ];
    foreach ($legacyColumns as $columnName => $definition) {
        if (!in_array($columnName, $transactionColumnNames, true)) {
            $pdo->exec("ALTER TABLE transactions ADD COLUMN {$columnName} {$definition}");
        }
    }
    $pdo->exec('UPDATE transactions SET installment_amount = amount WHERE installment_amount IS NULL OR installment_amount = 0');

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_transactions_user_month ON transactions (user_id, reference_month)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_transactions_period ON transactions (period_type, period_due_date)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_transactions_card ON transactions (card_id)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS incomes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            reference_month TEXT NOT NULL,
            period_type TEXT NOT NULL,
            payment_date TEXT NOT NULL,
            gross_salary REAL NOT NULL DEFAULT 0,
            benefits REAL NOT NULL DEFAULT 0,
            night_shift REAL NOT NULL DEFAULT 0,
            hazard_pay REAL NOT NULL DEFAULT 0,
            extra_income REAL NOT NULL DEFAULT 0,
            inss_discount REAL NOT NULL DEFAULT 0,
            transport_discount REAL NOT NULL DEFAULT 0,
            other_discounts REAL NOT NULL DEFAULT 0,
            net_amount REAL NOT NULL DEFAULT 0,
            payload_json TEXT DEFAULT '',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            UNIQUE (user_id, reference_month, period_type),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cards (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            nickname TEXT NOT NULL,
            institution TEXT DEFAULT '',
            brand TEXT DEFAULT '',
            bin4 TEXT DEFAULT '',
            last4 TEXT DEFAULT '',
            product_name TEXT DEFAULT '',
            cardholder_name TEXT DEFAULT '',
            expiry_date TEXT DEFAULT '',
            closing_day INTEGER NOT NULL DEFAULT 1,
            due_day INTEGER NOT NULL DEFAULT 10,
            limit_total REAL NOT NULL DEFAULT 0,
            active INTEGER NOT NULL DEFAULT 1,
            notes TEXT DEFAULT '',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    $cardColumns = $pdo->query('PRAGMA table_info(cards)')->fetchAll();
    $cardColumnNames = array_column($cardColumns, 'name');
    if (!in_array('institution', $cardColumnNames, true)) {
        $pdo->exec("ALTER TABLE cards ADD COLUMN institution TEXT DEFAULT ''");
    }
    if (!in_array('bin4', $cardColumnNames, true)) {
        $pdo->exec("ALTER TABLE cards ADD COLUMN bin4 TEXT DEFAULT ''");
    }
    if (!in_array('cardholder_name', $cardColumnNames, true)) {
        $pdo->exec("ALTER TABLE cards ADD COLUMN cardholder_name TEXT DEFAULT ''");
    }
    if (!in_array('expiry_date', $cardColumnNames, true)) {
        $pdo->exec("ALTER TABLE cards ADD COLUMN expiry_date TEXT DEFAULT ''");
    }
    if (!in_array('product_name', $cardColumnNames, true)) {
        $pdo->exec("ALTER TABLE cards ADD COLUMN product_name TEXT DEFAULT ''");
    }

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_cards_user ON cards (user_id, active)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS goals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            target_amount REAL NOT NULL DEFAULT 0,
            saved_amount REAL NOT NULL DEFAULT 0,
            reference_month TEXT NOT NULL,
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_goals_user_month ON goals (user_id, reference_month, active)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS imports (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            import_type TEXT NOT NULL DEFAULT 'extrato',
            file_name TEXT DEFAULT '',
            extracted_text TEXT DEFAULT '',
            movements_json TEXT DEFAULT '',
            status TEXT NOT NULL DEFAULT 'rascunho',
            created_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bank_connections (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            bank_name TEXT DEFAULT '',
            provider TEXT DEFAULT 'open_finance',
            consent_id TEXT DEFAULT '',
            status TEXT NOT NULL DEFAULT 'pendente',
            metadata_json TEXT DEFAULT '',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS professional_assets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            asset_type TEXT NOT NULL DEFAULT 'vehicle',
            name TEXT NOT NULL,
            description TEXT DEFAULT '',
            identifier TEXT DEFAULT '',
            estimated_value REAL NOT NULL DEFAULT 0,
            monthly_cost REAL NOT NULL DEFAULT 0,
            annual_tax REAL NOT NULL DEFAULT 0,
            next_due_date TEXT DEFAULT '',
            status TEXT NOT NULL DEFAULT 'ativo',
            metadata_json TEXT DEFAULT '',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_professional_assets_user ON professional_assets (user_id, asset_type, status)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS professional_taxes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            tax_type TEXT NOT NULL DEFAULT 'outro',
            competence_month TEXT NOT NULL,
            due_date TEXT NOT NULL,
            amount REAL NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'pendente',
            paid_date TEXT DEFAULT '',
            notes TEXT DEFAULT '',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_professional_taxes_user_month ON professional_taxes (user_id, competence_month, due_date)");

    // Modulos profissionais separados para manter cadastros, caixa e fiscal independentes.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS professional_clients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            document TEXT DEFAULT '',
            phone TEXT DEFAULT '',
            email TEXT DEFAULT '',
            notes TEXT DEFAULT '',
            status TEXT NOT NULL DEFAULT 'ativo',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_professional_clients_user ON professional_clients (user_id, status, name)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS professional_catalog (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            item_type TEXT NOT NULL DEFAULT 'product',
            name TEXT NOT NULL,
            code TEXT DEFAULT '',
            price REAL NOT NULL DEFAULT 0,
            cost REAL NOT NULL DEFAULT 0,
            stock REAL NOT NULL DEFAULT 0,
            active INTEGER NOT NULL DEFAULT 1,
            notes TEXT DEFAULT '',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_professional_catalog_user ON professional_catalog (user_id, item_type, active, name)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS professional_cash_accounts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            account_type TEXT NOT NULL DEFAULT 'conta_corrente',
            balance REAL NOT NULL DEFAULT 0,
            notes TEXT DEFAULT '',
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_professional_cash_user ON professional_cash_accounts (user_id, active, name)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS professional_categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            kind TEXT NOT NULL DEFAULT 'despesa',
            color TEXT DEFAULT '#1b9a68',
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_professional_categories_user ON professional_categories (user_id, active, kind, name)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS professional_payment_methods (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            method_type TEXT NOT NULL DEFAULT 'outro',
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_professional_payment_methods_user ON professional_payment_methods (user_id, active, name)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS professional_fiscal_documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            document_type TEXT NOT NULL DEFAULT 'nfse',
            document_number TEXT DEFAULT '',
            customer_name TEXT DEFAULT '',
            issue_date TEXT NOT NULL,
            amount REAL NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'emitido',
            notes TEXT DEFAULT '',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_professional_fiscal_user ON professional_fiscal_documents (user_id, issue_date, status)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS professional_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL UNIQUE,
            legal_name TEXT DEFAULT '',
            trade_name TEXT DEFAULT '',
            cnpj TEXT DEFAULT '',
            phone TEXT DEFAULT '',
            city TEXT DEFAULT '',
            notification_days INTEGER NOT NULL DEFAULT 7,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS professional_permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL UNIQUE,
            role_code TEXT NOT NULL DEFAULT 'owner',
            can_view_finance INTEGER NOT NULL DEFAULT 1,
            can_manage_clients INTEGER NOT NULL DEFAULT 1,
            can_manage_catalog INTEGER NOT NULL DEFAULT 1,
            can_manage_fiscal INTEGER NOT NULL DEFAULT 1,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS professional_schedule (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            schedule_type TEXT NOT NULL DEFAULT 'compromisso',
            due_date TEXT NOT NULL,
            due_time TEXT DEFAULT '',
            amount REAL NOT NULL DEFAULT 0,
            client_name TEXT DEFAULT '',
            status TEXT NOT NULL DEFAULT 'agendado',
            notes TEXT DEFAULT '',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_professional_schedule_user ON professional_schedule (user_id, due_date, status)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS professional_credit_checks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            client_id INTEGER DEFAULT NULL,
            document_type TEXT NOT NULL DEFAULT 'cnpj',
            document_masked TEXT NOT NULL DEFAULT '',
            provider TEXT NOT NULL DEFAULT 'combined',
            purpose TEXT NOT NULL DEFAULT 'analise_comercial',
            consented INTEGER NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'pendente',
            summary_json TEXT DEFAULT '{}',
            requested_at TEXT NOT NULL,
            expires_at TEXT DEFAULT '',
            created_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (client_id) REFERENCES professional_clients(id) ON DELETE SET NULL
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_professional_credit_checks_user ON professional_credit_checks (user_id, requested_at DESC)");
}

function sf_default_user(PDO $pdo): void
{
    $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $now = sf_now();
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, cpf, username, profile_type, profile_completed, profile_answers_json, password_hash, is_admin, must_change_password, created_at, updated_at)
        VALUES (:name, :email, :cpf, :username, 'clt', 1, '{}', :password_hash, 1, 1, :created_at, :updated_at)
    ");
    $stmt->execute([
        ':name' => 'Administrador',
        ':email' => SF_DEFAULT_EMAIL,
        ':cpf' => SF_DEFAULT_CPF,
        ':username' => SF_DEFAULT_USERNAME,
        ':password_hash' => password_hash(SF_DEFAULT_PASSWORD, PASSWORD_DEFAULT),
        ':created_at' => $now,
        ':updated_at' => $now,
    ]);
}

function sf_user_payload(array $user): array
{
    $profileType = sf_profile_type($user['profile_type'] ?? 'clt');
    $profile = sf_profile_catalog()[$profileType];
    return [
        'id' => (int)$user['id'],
        'name' => (string)$user['name'],
        'email' => (string)$user['email'],
        'cpf' => (string)($user['cpf'] ?? ''),
        'username' => (string)($user['username'] ?? ''),
        'isAdmin' => (bool)($user['is_admin'] ?? false),
        'mustChangePassword' => (bool)($user['must_change_password'] ?? false),
        'profileType' => $profileType,
        'profileCompleted' => (bool)($user['profile_completed'] ?? true),
        'profileAnswers' => json_decode((string)($user['profile_answers_json'] ?? '{}'), true) ?: [],
        'profileLabel' => $profile['label'],
        'profileDescription' => $profile['description'],
        'profileModules' => $profile['modules'],
    ];
}

function sf_profile_catalog(): array
{
    return [
        'clt' => [
            'label' => 'CLT',
            'description' => 'Salario, holerite, descontos e recebimentos por periodo.',
            'modules' => ['Holerite', 'Recebimentos', 'Cartoes', 'Contas e metas'],
        ],
        'pj' => [
            'label' => 'Prestador PJ',
            'description' => 'Faturamento, recebimentos de clientes, impostos e pro-labore.',
            'modules' => ['Receitas PJ', 'Despesas da empresa', 'Veiculos e imoveis', 'Impostos e impostometro', 'Recursos premium'],
        ],
        'comerciante_pf' => [
            'label' => 'Comerciante PF',
            'description' => 'Vendas, entradas, compras e controle do caixa pessoal.',
            'modules' => ['Vendas e recebimentos', 'Compras', 'Veiculos e imoveis', 'Impostos e impostometro', 'Recursos premium'],
        ],
        'comerciante_pj' => [
            'label' => 'Comerciante PJ',
            'description' => 'Vendas da empresa, despesas operacionais, fornecedores e caixa.',
            'modules' => ['Vendas da empresa', 'Despesas operacionais', 'Veiculos e imoveis', 'Impostos e impostometro', 'Recursos premium'],
        ],
    ];
}

function sf_profile_type($value): string
{
    $type = strtolower(sf_text($value ?? '', 40));
    return array_key_exists($type, sf_profile_catalog()) ? $type : 'clt';
}

function sf_infer_profile_type(array $answers): string
{
    $activity = strtolower(sf_text($answers['mainActivity'] ?? '', 40));
    $hasCnpj = strtolower(sf_text($answers['hasCnpj'] ?? '', 10)) === 'sim';
    $sellsProducts = strtolower(sf_text($answers['sellsProducts'] ?? '', 10)) === 'sim';
    $receivesSalary = strtolower(sf_text($answers['receivesSalary'] ?? '', 10)) === 'sim' || $activity === 'clt';

    if ($receivesSalary && !$sellsProducts && $activity !== 'services') {
        return 'clt';
    }
    if ($sellsProducts || $activity === 'commerce') {
        return $hasCnpj ? 'comerciante_pj' : 'comerciante_pf';
    }
    if ($activity === 'services' || $hasCnpj) {
        return 'pj';
    }
    return 'clt';
}

function sf_current_user(PDO $pdo): ?array
{
    sf_start_session();
    $userId = (int)($_SESSION['sf_user_id'] ?? 0);
    if ($userId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function sf_require_user(PDO $pdo): array
{
    $user = sf_current_user($pdo);
    if (!$user) {
        sf_json(['ok' => false, 'message' => 'Sessao expirada. Faca login novamente.'], 401);
    }
    return $user;
}

function sf_require_admin(PDO $pdo): array
{
    $user = sf_require_user($pdo);
    if (empty($user['is_admin'])) {
        sf_json(['ok' => false, 'message' => 'Somente o administrador pode autorizar novos usuarios.'], 403);
    }
    return $user;
}

function sf_admin_user_payload(array $user): array
{
    return [
        'id' => (int)$user['id'],
        'name' => (string)$user['name'],
        'username' => (string)($user['username'] ?? ''),
        'email' => (string)$user['email'],
        'profileType' => sf_profile_type($user['profile_type'] ?? 'clt'),
        'profileCompleted' => (bool)($user['profile_completed'] ?? false),
        'isAdmin' => (bool)($user['is_admin'] ?? false),
        'createdAt' => (string)($user['created_at'] ?? ''),
    ];
}

function sf_admin_users(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, name, username, email, profile_type, profile_completed, is_admin, created_at FROM users ORDER BY is_admin DESC, name COLLATE NOCASE ASC, id DESC');
    return array_map('sf_admin_user_payload', $stmt->fetchAll());
}

function sf_text($value, int $limit = 255): string
{
    $text = trim((string)$value);
    $text = preg_replace('/\s+/', ' ', $text);
    return substr($text, 0, $limit);
}

function sf_money($value): float
{
    if (is_int($value) || is_float($value)) {
        return round((float)$value, 2);
    }

    $text = trim((string)$value);
    if ($text === '') {
        return 0.0;
    }

    $negative = str_contains($text, '-');
    $text = preg_replace('/[^\d,.-]/', '', $text);
    $lastComma = strrpos($text, ',');
    $lastDot = strrpos($text, '.');
    if ($lastComma !== false && $lastDot !== false) {
        if ($lastComma > $lastDot) {
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        } else {
            $text = str_replace(',', '', $text);
        }
    } elseif ($lastComma !== false) {
        $decimalDigits = strlen($text) - $lastComma - 1;
        $text = $decimalDigits === 2 ? str_replace(',', '.', $text) : str_replace(',', '', $text);
    }

    $number = is_numeric($text) ? (float)$text : 0.0;
    return round($negative ? -abs($number) : $number, 2);
}

function sf_month($value = null): string
{
    $text = trim((string)$value);
    if (preg_match('/^\d{4}-\d{2}$/', $text)) {
        return $text;
    }
    return date('Y-m');
}

function sf_date($value = null): string
{
    $text = trim((string)$value);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $text)) {
        return $text;
    }
    return date('Y-m-d');
}

function sf_time($value = null): string
{
    $text = trim((string)$value);
    if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $text)) {
        return substr($text, 0, 5);
    }
    return '';
}

function sf_period($value = null): string
{
    $text = strtolower(trim((string)$value));
    return in_array($text, ['dia20', 'quinto_util'], true) ? $text : 'dia20';
}

function sf_easter_date_string(int $year, int $offsetDays): string
{
    $easter = new DateTimeImmutable('@' . easter_date($year));
    return $easter->setTimezone(new DateTimeZone(date_default_timezone_get()))->modify(($offsetDays >= 0 ? '+' : '') . $offsetDays . ' days')->format('Y-m-d');
}

function sf_national_holidays(int $year): array
{
    $fixed = [
        sprintf('%04d-01-01', $year),
        sprintf('%04d-04-21', $year),
        sprintf('%04d-05-01', $year),
        sprintf('%04d-09-07', $year),
        sprintf('%04d-10-12', $year),
        sprintf('%04d-11-02', $year),
        sprintf('%04d-11-15', $year),
        sprintf('%04d-11-20', $year),
        sprintf('%04d-12-25', $year),
    ];

    $movable = [
        sf_easter_date_string($year, -48),
        sf_easter_date_string($year, -47),
        sf_easter_date_string($year, -2),
        sf_easter_date_string($year, 60),
    ];

    return array_fill_keys(array_merge($fixed, $movable), true);
}

function sf_fifth_business_day(int $year, int $month): string
{
    $date = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
    $holidays = sf_national_holidays($year);
    $count = 0;

    while (true) {
        $weekDay = (int)$date->format('N');
        $iso = $date->format('Y-m-d');
        if ($weekDay <= 5 && empty($holidays[$iso])) {
            $count++;
            if ($count === 5) {
                return $iso;
            }
        }
        $date = $date->modify('+1 day');
    }
}

function sf_period_due_date(string $referenceMonth, string $periodType): string
{
    [$year, $month] = array_map('intval', explode('-', sf_month($referenceMonth)));
    if (sf_period($periodType) === 'quinto_util') {
        $next = (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->modify('+1 month');
        return sf_fifth_business_day((int)$next->format('Y'), (int)$next->format('m'));
    }

    return sprintf('%04d-%02d-20', $year, $month);
}

function sf_month_periods(string $referenceMonth): array
{
    return [
        [
            'type' => 'dia20',
            'label' => 'Dia 20',
            'description' => 'Adiantamento e contas do dia 20.',
            'dueDate' => sf_period_due_date($referenceMonth, 'dia20'),
        ],
        [
            'type' => 'quinto_util',
            'label' => '5º dia útil',
            'description' => 'Pagamento do 5º dia útil do mês seguinte.',
            'dueDate' => sf_period_due_date($referenceMonth, 'quinto_util'),
        ],
    ];
}

function sf_inss_2026(float $base): float
{
    $bands = [
        ['limit' => 1621.00, 'rate' => 0.075],
        ['limit' => 2902.84, 'rate' => 0.09],
        ['limit' => 4354.27, 'rate' => 0.12],
        ['limit' => 8475.55, 'rate' => 0.14],
    ];

    $base = max(0, min($base, 8475.55));
    $previous = 0.0;
    $total = 0.0;
    foreach ($bands as $band) {
        if ($base <= $previous) {
            break;
        }
        $taxable = min($base, $band['limit']) - $previous;
        if ($taxable > 0) {
            $total += $taxable * $band['rate'];
        }
        $previous = $band['limit'];
    }

    return round($total, 2);
}
