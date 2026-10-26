-- =====================================================
-- Banco convertido de SQLite para MySQL / MariaDB
-- Gerado automaticamente a partir de financeiro.sqlite
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `cpf` VARCHAR(255) DEFAULT '',
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` VARCHAR(255) NOT NULL,
  `updated_at` VARCHAR(255) NOT NULL,
  `profile_type` VARCHAR(255) NOT NULL DEFAULT 'clt',
  `profile_completed` INT NOT NULL DEFAULT 1,
  `profile_answers_json` LONGTEXT DEFAULT '{}',
  `username` VARCHAR(255) DEFAULT '',
  `is_admin` INT NOT NULL DEFAULT 0,
  `must_change_password` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_users_username` (`username`),
  UNIQUE KEY `sqlite_autoindex_users_1` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bank_connections`;
CREATE TABLE `bank_connections` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `bank_name` VARCHAR(255) DEFAULT '',
  `provider` VARCHAR(255) DEFAULT 'open_finance',
  `consent_id` VARCHAR(255) DEFAULT '',
  `status` VARCHAR(255) NOT NULL DEFAULT 'pendente',
  `metadata_json` LONGTEXT DEFAULT '',
  `created_at` VARCHAR(255) NOT NULL,
  `updated_at` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_bank_connections_0` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cards`;
CREATE TABLE `cards` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `nickname` VARCHAR(255) NOT NULL,
  `brand` VARCHAR(255) DEFAULT '',
  `last4` VARCHAR(255) DEFAULT '',
  `closing_day` INT NOT NULL DEFAULT 1,
  `due_day` INT NOT NULL DEFAULT 10,
  `limit_total` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `active` INT NOT NULL DEFAULT 1,
  `notes` LONGTEXT DEFAULT '',
  `created_at` VARCHAR(255) NOT NULL,
  `updated_at` VARCHAR(255) NOT NULL,
  `institution` VARCHAR(255) DEFAULT '',
  `bin4` VARCHAR(255) DEFAULT '',
  `cardholder_name` VARCHAR(255) DEFAULT '',
  `expiry_date` VARCHAR(255) DEFAULT '',
  `product_name` VARCHAR(255) DEFAULT '',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_cards_0` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `goals`;
CREATE TABLE `goals` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `target_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `saved_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `reference_month` VARCHAR(255) NOT NULL,
  `active` INT NOT NULL DEFAULT 1,
  `created_at` VARCHAR(255) NOT NULL,
  `updated_at` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_goals_0` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `imports`;
CREATE TABLE `imports` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `import_type` VARCHAR(255) NOT NULL DEFAULT 'extrato',
  `file_name` VARCHAR(255) DEFAULT '',
  `extracted_text` LONGTEXT DEFAULT '',
  `movements_json` LONGTEXT DEFAULT '',
  `status` VARCHAR(255) NOT NULL DEFAULT 'rascunho',
  `created_at` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_imports_0` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `incomes`;
CREATE TABLE `incomes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `reference_month` VARCHAR(255) NOT NULL,
  `period_type` VARCHAR(255) NOT NULL,
  `payment_date` VARCHAR(255) NOT NULL,
  `gross_salary` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `benefits` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `night_shift` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `hazard_pay` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `extra_income` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `inss_discount` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `transport_discount` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `other_discounts` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `net_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `payload_json` LONGTEXT DEFAULT '',
  `created_at` VARCHAR(255) NOT NULL,
  `updated_at` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sqlite_autoindex_incomes_1` (`user_id`, `reference_month`, `period_type`),
  CONSTRAINT `fk_incomes_0` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `professional_assets`;
CREATE TABLE `professional_assets` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `asset_type` VARCHAR(255) NOT NULL DEFAULT 'vehicle',
  `name` VARCHAR(255) NOT NULL,
  `description` LONGTEXT DEFAULT '',
  `identifier` VARCHAR(255) DEFAULT '',
  `estimated_value` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `monthly_cost` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `annual_tax` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `next_due_date` VARCHAR(255) DEFAULT '',
  `status` VARCHAR(255) NOT NULL DEFAULT 'ativo',
  `metadata_json` LONGTEXT DEFAULT '',
  `created_at` VARCHAR(255) NOT NULL,
  `updated_at` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_professional_assets_0` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `professional_cash_accounts`;
CREATE TABLE `professional_cash_accounts` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `account_type` VARCHAR(255) NOT NULL DEFAULT 'conta_corrente',
  `balance` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `notes` LONGTEXT DEFAULT '',
  `active` INT NOT NULL DEFAULT 1,
  `created_at` VARCHAR(255) NOT NULL,
  `updated_at` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_professional_cash_accounts_0` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `professional_catalog`;
CREATE TABLE `professional_catalog` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `item_type` VARCHAR(255) NOT NULL DEFAULT 'product',
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(255) DEFAULT '',
  `price` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `cost` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `stock` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `active` INT NOT NULL DEFAULT 1,
  `notes` LONGTEXT DEFAULT '',
  `created_at` VARCHAR(255) NOT NULL,
  `updated_at` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_professional_catalog_0` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `professional_categories`;
CREATE TABLE `professional_categories` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `kind` VARCHAR(255) NOT NULL DEFAULT 'despesa',
  `color` VARCHAR(255) DEFAULT '#1b9a68',
  `active` INT NOT NULL DEFAULT 1,
  `created_at` VARCHAR(255) NOT NULL,
  `updated_at` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_professional_categories_0` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `professional_clients`;
CREATE TABLE `professional_clients` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `document` VARCHAR(255) DEFAULT '',
  `phone` VARCHAR(255) DEFAULT '',
  `email` VARCHAR(255) DEFAULT '',
  `notes` LONGTEXT DEFAULT '',
  `status` VARCHAR(255) NOT NULL DEFAULT 'ativo',
  `created_at` VARCHAR(255) NOT NULL,
  `updated_at` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_professional_clients_0` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `professional_credit_checks`;
CREATE TABLE `professional_credit_checks` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `client_id` INT DEFAULT NULL,
  `document_type` VARCHAR(255) NOT NULL DEFAULT 'cnpj',
  `document_masked` VARCHAR(255) NOT NULL DEFAULT '',
  `provider` VARCHAR(255) NOT NULL DEFAULT 'combined',
  `purpose` VARCHAR(255) NOT NULL DEFAULT 'analise_comercial',
  `consented` INT NOT NULL DEFAULT 0,
  `status` VARCHAR(255) NOT NULL DEFAULT 'pendente',
  `summary_json` LONGTEXT DEFAULT '{}',
  `requested_at` VARCHAR(255) NOT NULL,
  `expires_at` VARCHAR(255) DEFAULT '',
  `created_at` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_professional_credit_checks_0` FOREIGN KEY (`client_id`) REFERENCES `professional_clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_professional_credit_checks_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `professional_fiscal_documents`;
CREATE TABLE `professional_fiscal_documents` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `document_type` VARCHAR(255) NOT NULL DEFAULT 'nfse',
  `document_number` VARCHAR(255) DEFAULT '',
  `customer_name` VARCHAR(255) DEFAULT '',
  `issue_date` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `status` VARCHAR(255) NOT NULL DEFAULT 'emitido',
  `notes` LONGTEXT DEFAULT '',
  `created_at` VARCHAR(255) NOT NULL,
  `updated_at` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_professional_fiscal_documents_0` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `professional_payment_methods`;
CREATE TABLE `professional_payment_methods` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `method_type` VARCHAR(255) NOT NULL DEFAULT 'outro',
  `active` INT NOT NULL DEFAULT 1,
  `created_at` VARCHAR(255) NOT NULL,
  `updated_at` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_professional_payment_methods_0` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `professional_permissions`;
CREATE TABLE `professional_permissions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `role_code` VARCHAR(255) NOT NULL DEFAULT 'owner',
  `can_view_finance` INT NOT NULL DEFAULT 1,
  `can_manage_clients` INT NOT NULL DEFAULT 1,
  `can_manage_catalog` INT NOT NULL DEFAULT 1,
  `can_manage_fiscal` INT NOT NULL DEFAULT 1,
  `updated_at` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sqlite_autoindex_professional_permissions_1` (`user_id`),
  CONSTRAINT `fk_professional_permissions_0` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `professional_schedule`;
CREATE TABLE `professional_schedule` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `schedule_type` VARCHAR(255) NOT NULL DEFAULT 'compromisso',
  `due_date` VARCHAR(255) NOT NULL,
  `due_time` VARCHAR(255) DEFAULT '',
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `client_name` VARCHAR(255) DEFAULT '',
  `status` VARCHAR(255) NOT NULL DEFAULT 'agendado',
  `notes` LONGTEXT DEFAULT '',
  `created_at` VARCHAR(255) NOT NULL,
  `updated_at` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_professional_schedule_0` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `professional_settings`;
CREATE TABLE `professional_settings` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `legal_name` VARCHAR(255) DEFAULT '',
  `trade_name` VARCHAR(255) DEFAULT '',
  `cnpj` VARCHAR(255) DEFAULT '',
  `phone` VARCHAR(255) DEFAULT '',
  `city` VARCHAR(255) DEFAULT '',
  `notification_days` INT NOT NULL DEFAULT 7,
  `updated_at` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sqlite_autoindex_professional_settings_1` (`user_id`),
  CONSTRAINT `fk_professional_settings_0` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `professional_taxes`;
CREATE TABLE `professional_taxes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `tax_type` VARCHAR(255) NOT NULL DEFAULT 'outro',
  `competence_month` VARCHAR(255) NOT NULL,
  `due_date` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `status` VARCHAR(255) NOT NULL DEFAULT 'pendente',
  `paid_date` VARCHAR(255) DEFAULT '',
  `notes` LONGTEXT DEFAULT '',
  `created_at` VARCHAR(255) NOT NULL,
  `updated_at` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_professional_taxes_0` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `type` VARCHAR(255) NOT NULL DEFAULT 'saida',
  `description` LONGTEXT NOT NULL,
  `category` VARCHAR(255) DEFAULT '',
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `movement_date` VARCHAR(255) NOT NULL,
  `payment_method` VARCHAR(255) DEFAULT 'pix',
  `period_type` VARCHAR(255) NOT NULL DEFAULT 'dia20',
  `reference_month` VARCHAR(255) NOT NULL,
  `period_due_date` VARCHAR(255) DEFAULT '',
  `card_id` INT DEFAULT NULL,
  `source` VARCHAR(255) DEFAULT 'manual',
  `status` VARCHAR(255) DEFAULT 'pago',
  `notes` LONGTEXT DEFAULT '',
  `import_id` INT DEFAULT NULL,
  `created_at` VARCHAR(255) NOT NULL,
  `updated_at` VARCHAR(255) NOT NULL,
  `movement_time` VARCHAR(255) DEFAULT '',
  `installment_count` INT NOT NULL DEFAULT 1,
  `installment_number` INT NOT NULL DEFAULT 1,
  `installment_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `installment_group` VARCHAR(255) DEFAULT '',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_transactions_0` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados da tabela users
INSERT INTO `users` (`id`, `name`, `email`, `cpf`, `password_hash`, `created_at`, `updated_at`, `profile_type`, `profile_completed`, `profile_answers_json`, `username`, `is_admin`, `must_change_password`) VALUES (4, 'Administrador', 'admin@controle-financeiro.local', '', '$2y$10$pD3fwaskGIPzAY05gNad8udVkE2sqtpe6fMrN8wOjaZ5Dw7oyKI/q', '2026-09-11 04:39:59', '2026-09-11 04:48:05', 'clt', 1, '{}', 'admin', 1, 0);
INSERT INTO `users` (`id`, `name`, `email`, `cpf`, `password_hash`, `created_at`, `updated_at`, `profile_type`, `profile_completed`, `profile_answers_json`, `username`, `is_admin`, `must_change_password`) VALUES (5, 'BRENO ANACLETO ALVES DA CRUZ', 'brenoanacleto29@hotmail.com', '15416282681', '$2y$10$gvNys2zziJPppdqYqY4gYezHjLbhf51Eyz0vGgOEEXAgdvNWqYZea', '2026-09-11 04:53:28', '2026-09-11 04:54:17', 'comerciante_pj', 1, '{"mainActivity":"commerce","receivesSalary":"nao","hasCnpj":"sim","sellsProducts":"sim","hasVehicles":"sim","hasProperties":"sim","taxControl":"sim"}', '15416282681', 0, 0);

-- Dados da tabela cards
INSERT INTO `cards` (`id`, `user_id`, `nickname`, `brand`, `last4`, `closing_day`, `due_day`, `limit_total`, `active`, `notes`, `created_at`, `updated_at`, `institution`, `bin4`, `cardholder_name`, `expiry_date`, `product_name`) VALUES (20, 4, 'SANTANDER FREE', 'Elo', '0855', 5, 10, 24000, 1, '', '2026-09-11 05:11:58', '2026-09-11 05:11:58', 'Santander', '', 'CARTÃO TESTE', '01/28', 'Free');

-- Dados da tabela transactions
INSERT INTO `transactions` (`id`, `user_id`, `type`, `description`, `category`, `amount`, `movement_date`, `payment_method`, `period_type`, `reference_month`, `period_due_date`, `card_id`, `source`, `status`, `notes`, `import_id`, `created_at`, `updated_at`, `movement_time`, `installment_count`, `installment_number`, `installment_amount`, `installment_group`) VALUES (31, 4, 'saida', 'TESTE', 'TRANSPORTE', 500, '2026-10-11', 'credito', 'quinto_util', '2026-10', '2026-11-09', 20, 'manual', 'pago', '', NULL, '2026-09-11 05:13:14', '2026-09-11 05:13:14', '', 1, 1, 500, '');

SET FOREIGN_KEY_CHECKS = 1;
