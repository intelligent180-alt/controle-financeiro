-- Export SQL gerado a partir de financeiro.sqlite
-- Banco verificado com PRAGMA integrity_check = ok
PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;
CREATE TABLE bank_connections (
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
        );
CREATE TABLE cards (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            nickname TEXT NOT NULL,
            brand TEXT DEFAULT '',
            last4 TEXT DEFAULT '',
            closing_day INTEGER NOT NULL DEFAULT 1,
            due_day INTEGER NOT NULL DEFAULT 10,
            limit_total REAL NOT NULL DEFAULT 0,
            active INTEGER NOT NULL DEFAULT 1,
            notes TEXT DEFAULT '',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL, institution TEXT DEFAULT '', bin4 TEXT DEFAULT '', cardholder_name TEXT DEFAULT '', expiry_date TEXT DEFAULT '', product_name TEXT DEFAULT '',
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
INSERT INTO "cards" VALUES(20,4,'SANTANDER FREE','Elo','0855',5,10,24000.0,1,'','2026-09-11 05:11:58','2026-09-11 05:11:58','Santander','','CARTÃO TESTE','01/28','Free');
CREATE TABLE goals (
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
        );
CREATE TABLE imports (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            import_type TEXT NOT NULL DEFAULT 'extrato',
            file_name TEXT DEFAULT '',
            extracted_text TEXT DEFAULT '',
            movements_json TEXT DEFAULT '',
            status TEXT NOT NULL DEFAULT 'rascunho',
            created_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
CREATE TABLE incomes (
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
        );
CREATE TABLE professional_assets (
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
        );
CREATE TABLE professional_cash_accounts (
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
        );
CREATE TABLE professional_catalog (
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
        );
CREATE TABLE professional_categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            kind TEXT NOT NULL DEFAULT 'despesa',
            color TEXT DEFAULT '#1b9a68',
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
CREATE TABLE professional_clients (
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
        );
CREATE TABLE professional_credit_checks (
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
        );
CREATE TABLE professional_fiscal_documents (
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
        );
CREATE TABLE professional_payment_methods (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            method_type TEXT NOT NULL DEFAULT 'outro',
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
CREATE TABLE professional_permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL UNIQUE,
            role_code TEXT NOT NULL DEFAULT 'owner',
            can_view_finance INTEGER NOT NULL DEFAULT 1,
            can_manage_clients INTEGER NOT NULL DEFAULT 1,
            can_manage_catalog INTEGER NOT NULL DEFAULT 1,
            can_manage_fiscal INTEGER NOT NULL DEFAULT 1,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
CREATE TABLE professional_schedule (
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
        );
CREATE TABLE professional_settings (
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
        );
CREATE TABLE professional_taxes (
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
        );
CREATE TABLE transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            type TEXT NOT NULL DEFAULT 'saida',
            description TEXT NOT NULL,
            category TEXT DEFAULT '',
            amount REAL NOT NULL DEFAULT 0,
            movement_date TEXT NOT NULL,
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
            updated_at TEXT NOT NULL, movement_time TEXT DEFAULT '', installment_count INTEGER NOT NULL DEFAULT 1, installment_number INTEGER NOT NULL DEFAULT 1, installment_amount REAL NOT NULL DEFAULT 0, installment_group TEXT DEFAULT '',
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
INSERT INTO "transactions" VALUES(31,4,'saida','TESTE','TRANSPORTE',500.0,'2026-10-11','credito','quinto_util','2026-10','2026-11-09',20,'manual','pago','',NULL,'2026-09-11 05:13:14','2026-09-11 05:13:14','',1,1,500.0,'');
CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            cpf TEXT DEFAULT '',
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        , profile_type TEXT NOT NULL DEFAULT 'clt', profile_completed INTEGER NOT NULL DEFAULT 1, profile_answers_json TEXT DEFAULT '{}', username TEXT DEFAULT '', is_admin INTEGER NOT NULL DEFAULT 0, must_change_password INTEGER NOT NULL DEFAULT 0);
INSERT INTO "users" VALUES(4,'Administrador','admin@controle-financeiro.local','','$2y$10$pD3fwaskGIPzAY05gNad8udVkE2sqtpe6fMrN8wOjaZ5Dw7oyKI/q','2026-09-11 04:39:59','2026-09-11 04:48:05','clt',1,'{}','admin',1,0);
INSERT INTO "users" VALUES(5,'BRENO ANACLETO ALVES DA CRUZ','brenoanacleto29@hotmail.com','15416282681','$2y$10$gvNys2zziJPppdqYqY4gYezHjLbhf51Eyz0vGgOEEXAgdvNWqYZea','2026-09-11 04:53:28','2026-09-11 04:54:17','comerciante_pj',1,'{"mainActivity":"commerce","receivesSalary":"nao","hasCnpj":"sim","sellsProducts":"sim","hasVehicles":"sim","hasProperties":"sim","taxControl":"sim"}','15416282681',0,0);
CREATE INDEX idx_transactions_user_month ON transactions (user_id, reference_month);
CREATE INDEX idx_transactions_period ON transactions (period_type, period_due_date);
CREATE INDEX idx_transactions_card ON transactions (card_id);
CREATE INDEX idx_cards_user ON cards (user_id, active);
CREATE INDEX idx_goals_user_month ON goals (user_id, reference_month, active);
CREATE INDEX idx_professional_assets_user ON professional_assets (user_id, asset_type, status);
CREATE INDEX idx_professional_taxes_user_month ON professional_taxes (user_id, competence_month, due_date);
CREATE INDEX idx_professional_clients_user ON professional_clients (user_id, status, name);
CREATE INDEX idx_professional_catalog_user ON professional_catalog (user_id, item_type, active, name);
CREATE INDEX idx_professional_cash_user ON professional_cash_accounts (user_id, active, name);
CREATE INDEX idx_professional_categories_user ON professional_categories (user_id, active, kind, name);
CREATE INDEX idx_professional_payment_methods_user ON professional_payment_methods (user_id, active, name);
CREATE INDEX idx_professional_fiscal_user ON professional_fiscal_documents (user_id, issue_date, status);
CREATE INDEX idx_professional_schedule_user ON professional_schedule (user_id, due_date, status);
CREATE INDEX idx_professional_credit_checks_user ON professional_credit_checks (user_id, requested_at DESC);
CREATE UNIQUE INDEX idx_users_username ON users (username) WHERE username <> '';
DELETE FROM "sqlite_sequence";
INSERT INTO "sqlite_sequence" VALUES('users',5);
INSERT INTO "sqlite_sequence" VALUES('incomes',6);
INSERT INTO "sqlite_sequence" VALUES('cards',20);
INSERT INTO "sqlite_sequence" VALUES('transactions',31);
INSERT INTO "sqlite_sequence" VALUES('imports',5);
INSERT INTO "sqlite_sequence" VALUES('professional_clients',1);
INSERT INTO "sqlite_sequence" VALUES('professional_credit_checks',1);
COMMIT;
PRAGMA foreign_keys=ON;
