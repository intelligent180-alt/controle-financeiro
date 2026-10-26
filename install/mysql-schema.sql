-- Schema MySQL/MariaDB do Controle Financeiro.
-- O bootstrap tambem executa este arquivo automaticamente no primeiro acesso.

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL,
    cpf VARCHAR(20) NOT NULL DEFAULT '',
    username VARCHAR(50) DEFAULT NULL,
    profile_type VARCHAR(30) NOT NULL DEFAULT 'clt',
    profile_completed TINYINT(1) NOT NULL DEFAULT 1,
    profile_answers_json LONGTEXT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cards (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    nickname VARCHAR(160) NOT NULL,
    institution VARCHAR(120) NOT NULL DEFAULT '',
    brand VARCHAR(40) NOT NULL DEFAULT '',
    bin4 VARCHAR(4) NOT NULL DEFAULT '',
    last4 VARCHAR(4) NOT NULL DEFAULT '',
    product_name VARCHAR(120) NOT NULL DEFAULT '',
    cardholder_name VARCHAR(160) NOT NULL DEFAULT '',
    expiry_date VARCHAR(10) NOT NULL DEFAULT '',
    closing_day INT NOT NULL DEFAULT 1,
    due_day INT NOT NULL DEFAULT 10,
    limit_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    active TINYINT(1) NOT NULL DEFAULT 1,
    notes VARCHAR(1000) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_cards_user (user_id, active),
    CONSTRAINT fk_cards_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS imports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    import_type VARCHAR(40) NOT NULL DEFAULT 'extrato',
    file_name VARCHAR(255) NOT NULL DEFAULT '',
    extracted_text LONGTEXT NOT NULL,
    movements_json LONGTEXT NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'rascunho',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_imports_user (user_id, created_at),
    CONSTRAINT fk_imports_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'saida',
    description VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT '',
    amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    installment_count INT NOT NULL DEFAULT 1,
    installment_number INT NOT NULL DEFAULT 1,
    installment_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    installment_group VARCHAR(80) NOT NULL DEFAULT '',
    movement_date CHAR(10) NOT NULL,
    movement_time CHAR(5) NOT NULL DEFAULT '',
    payment_method VARCHAR(30) NOT NULL DEFAULT 'pix',
    period_type VARCHAR(30) NOT NULL DEFAULT 'dia20',
    reference_month CHAR(7) NOT NULL,
    period_due_date CHAR(10) NOT NULL DEFAULT '',
    card_id BIGINT UNSIGNED DEFAULT NULL,
    source VARCHAR(30) NOT NULL DEFAULT 'manual',
    status VARCHAR(30) NOT NULL DEFAULT 'pago',
    notes VARCHAR(1000) NOT NULL DEFAULT '',
    import_id BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_transactions_user_month (user_id, reference_month),
    KEY idx_transactions_period (period_type, period_due_date),
    KEY idx_transactions_card (card_id),
    CONSTRAINT fk_transactions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_transactions_card FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE SET NULL,
    CONSTRAINT fk_transactions_import FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS incomes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    reference_month CHAR(7) NOT NULL,
    period_type VARCHAR(30) NOT NULL,
    payment_date CHAR(10) NOT NULL,
    gross_salary DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    benefits DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    night_shift DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    hazard_pay DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    extra_income DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    inss_discount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    transport_discount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    other_discounts DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    net_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    payload_json LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_incomes_period (user_id, reference_month, period_type),
    CONSTRAINT fk_incomes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS goals (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    target_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    saved_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    reference_month CHAR(7) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_goals_user_month (user_id, reference_month, active),
    CONSTRAINT fk_goals_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bank_connections (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    bank_name VARCHAR(120) NOT NULL DEFAULT '',
    provider VARCHAR(60) NOT NULL DEFAULT 'open_finance',
    consent_id VARCHAR(190) NOT NULL DEFAULT '',
    status VARCHAR(30) NOT NULL DEFAULT 'pendente',
    metadata_json LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_bank_connections_user (user_id, status),
    CONSTRAINT fk_bank_connections_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS professional_assets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    asset_type VARCHAR(40) NOT NULL DEFAULT 'vehicle',
    name VARCHAR(160) NOT NULL,
    description VARCHAR(1000) NOT NULL DEFAULT '',
    identifier VARCHAR(120) NOT NULL DEFAULT '',
    estimated_value DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    monthly_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    annual_tax DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    next_due_date CHAR(10) NOT NULL DEFAULT '',
    status VARCHAR(30) NOT NULL DEFAULT 'ativo',
    metadata_json LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_professional_assets_user (user_id, asset_type, status),
    CONSTRAINT fk_professional_assets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS professional_taxes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    tax_type VARCHAR(40) NOT NULL DEFAULT 'outro',
    competence_month CHAR(7) NOT NULL,
    due_date CHAR(10) NOT NULL,
    amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(30) NOT NULL DEFAULT 'pendente',
    paid_date CHAR(10) NOT NULL DEFAULT '',
    notes VARCHAR(1000) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_professional_taxes_user_month (user_id, competence_month, due_date),
    CONSTRAINT fk_professional_taxes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS professional_clients (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    document VARCHAR(30) NOT NULL DEFAULT '',
    phone VARCHAR(40) NOT NULL DEFAULT '',
    email VARCHAR(190) NOT NULL DEFAULT '',
    notes VARCHAR(1000) NOT NULL DEFAULT '',
    status VARCHAR(30) NOT NULL DEFAULT 'ativo',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_professional_clients_user (user_id, status, name),
    CONSTRAINT fk_professional_clients_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS professional_catalog (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    item_type VARCHAR(30) NOT NULL DEFAULT 'product',
    name VARCHAR(160) NOT NULL,
    code VARCHAR(80) NOT NULL DEFAULT '',
    price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    stock DECIMAL(14,3) NOT NULL DEFAULT 0.000,
    active TINYINT(1) NOT NULL DEFAULT 1,
    notes VARCHAR(1000) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_professional_catalog_user (user_id, item_type, active, name),
    CONSTRAINT fk_professional_catalog_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS professional_cash_accounts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    account_type VARCHAR(40) NOT NULL DEFAULT 'conta_corrente',
    balance DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    notes VARCHAR(1000) NOT NULL DEFAULT '',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_professional_cash_user (user_id, active, name),
    CONSTRAINT fk_professional_cash_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS professional_categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    kind VARCHAR(30) NOT NULL DEFAULT 'despesa',
    color VARCHAR(20) NOT NULL DEFAULT '#1b9a68',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_professional_categories_user (user_id, active, kind, name),
    CONSTRAINT fk_professional_categories_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS professional_payment_methods (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    method_type VARCHAR(30) NOT NULL DEFAULT 'outro',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_professional_payment_methods_user (user_id, active, name),
    CONSTRAINT fk_professional_payment_methods_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS professional_fiscal_documents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    document_type VARCHAR(30) NOT NULL DEFAULT 'nfse',
    document_number VARCHAR(80) NOT NULL DEFAULT '',
    customer_name VARCHAR(160) NOT NULL DEFAULT '',
    issue_date CHAR(10) NOT NULL,
    amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(30) NOT NULL DEFAULT 'emitido',
    notes VARCHAR(1000) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_professional_fiscal_user (user_id, issue_date, status),
    CONSTRAINT fk_professional_fiscal_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS professional_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    legal_name VARCHAR(190) NOT NULL DEFAULT '',
    trade_name VARCHAR(190) NOT NULL DEFAULT '',
    cnpj VARCHAR(30) NOT NULL DEFAULT '',
    phone VARCHAR(40) NOT NULL DEFAULT '',
    city VARCHAR(120) NOT NULL DEFAULT '',
    notification_days INT NOT NULL DEFAULT 7,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_professional_settings_user (user_id),
    CONSTRAINT fk_professional_settings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS professional_permissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    role_code VARCHAR(30) NOT NULL DEFAULT 'owner',
    can_view_finance TINYINT(1) NOT NULL DEFAULT 1,
    can_manage_clients TINYINT(1) NOT NULL DEFAULT 1,
    can_manage_catalog TINYINT(1) NOT NULL DEFAULT 1,
    can_manage_fiscal TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_professional_permissions_user (user_id),
    CONSTRAINT fk_professional_permissions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS professional_schedule (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    schedule_type VARCHAR(40) NOT NULL DEFAULT 'compromisso',
    due_date CHAR(10) NOT NULL,
    due_time CHAR(5) NOT NULL DEFAULT '',
    amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    client_name VARCHAR(160) NOT NULL DEFAULT '',
    status VARCHAR(30) NOT NULL DEFAULT 'agendado',
    notes VARCHAR(1000) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_professional_schedule_user (user_id, due_date, status),
    CONSTRAINT fk_professional_schedule_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS professional_credit_checks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED DEFAULT NULL,
    document_type VARCHAR(20) NOT NULL DEFAULT 'cnpj',
    document_masked VARCHAR(40) NOT NULL DEFAULT '',
    provider VARCHAR(40) NOT NULL DEFAULT 'combined',
    purpose VARCHAR(80) NOT NULL DEFAULT 'analise_comercial',
    consented TINYINT(1) NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'pendente',
    summary_json LONGTEXT NOT NULL,
    requested_at DATETIME NOT NULL,
    expires_at VARCHAR(30) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_professional_credit_checks_user (user_id, requested_at),
    CONSTRAINT fk_professional_credit_checks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_professional_credit_checks_client FOREIGN KEY (client_id) REFERENCES professional_clients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS developer_api_keys (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    key_prefix VARCHAR(32) NOT NULL,
    key_hash CHAR(64) NOT NULL,
    scopes VARCHAR(255) NOT NULL DEFAULT 'read',
    active TINYINT(1) NOT NULL DEFAULT 1,
    rate_limit INT NOT NULL DEFAULT 120,
    last_used_at DATETIME NULL,
    window_started_at DATETIME NULL,
    window_requests INT NOT NULL DEFAULT 0,
    expires_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_developer_api_key_hash (key_hash),
    KEY idx_developer_api_keys_user (user_id, active),
    CONSTRAINT fk_developer_api_keys_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
