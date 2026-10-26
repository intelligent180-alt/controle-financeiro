<?php

declare(strict_types=1);

/**
 * Migracao controlada de um SQLite legado para MySQL.
 *
 * A pre-visualizacao nunca abre conexao MySQL e nunca altera o SQLite.
 * A importacao e aditiva: nao apaga registros e nao substitui dados
 * existentes. O perfil pode ser ajustado explicitamente para CLT.
 */

function sf_legacy_migration_run(
    string $sourceFile,
    string $cpf,
    string $profile = 'keep',
    bool $execute = false,
    string $confirmation = '',
    ?int $sourceUserId = null
): array {
    $sourceFile = trim($sourceFile);
    $cpf = preg_replace('/\D+/', '', $cpf) ?? '';
    $profile = strtolower(trim($profile));

    if ($sourceFile === '' || !is_file($sourceFile)) {
        throw new RuntimeException("SQLite legado nao encontrado: {$sourceFile}");
    }
    if ($cpf === '') {
        throw new RuntimeException('Informe um CPF para limitar a migracao a um usuario.');
    }
    if (!in_array($profile, ['keep', 'clt', 'pj', 'comerciante_pf', 'comerciante_pj'], true)) {
        throw new RuntimeException('Perfil invalido. Use keep, clt, pj, comerciante_pf ou comerciante_pj.');
    }
    if ($execute && $confirmation !== 'MIGRAR-DADOS-SEM-APAGAR') {
        throw new RuntimeException('Confirmacao obrigatoria: MIGRAR-DADOS-SEM-APAGAR.');
    }

    $source = new PDO('sqlite:' . $sourceFile, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $sourceUsers = sf_legacy_source_users($source, $cpf);
    if ($sourceUsers === []) {
        throw new RuntimeException('Nenhum usuario com esse CPF foi encontrado no SQLite legado.');
    }
    if ($sourceUserId !== null) {
        $sourceUsers = array_values(array_filter(
            $sourceUsers,
            static fn(array $user): bool => (int)($user['id'] ?? 0) === $sourceUserId
        ));
        if ($sourceUsers === []) {
            throw new RuntimeException('O --source-user-id nao pertence ao CPF informado no SQLite legado.');
        }
    }
    if ($execute && count($sourceUsers) > 1) {
        throw new RuntimeException(
            'Foram encontrados varios usuarios para este CPF. Execute a previa e informe --source-user-id com o ID correto.'
        );
    }

    $sourceTables = $source->query(
        "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
    )->fetchAll(PDO::FETCH_COLUMN);
    $tableReport = [];
    foreach ($sourceTables as $table) {
        $table = (string)$table;
        $columns = array_column($source->query('PRAGMA table_info(' . sf_legacy_quote($table) . ')')->fetchAll(), 'name');
        if ($table === 'users') {
            $tableReport[$table] = count($sourceUsers);
            continue;
        }
        if (!in_array('user_id', $columns, true)) {
            continue;
        }
        $ids = array_column($sourceUsers, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $source->prepare(
            'SELECT COUNT(*) FROM ' . sf_legacy_quote($table) . ' WHERE user_id IN (' . $placeholders . ')'
        );
        $statement->execute($ids);
        $tableReport[$table] = (int)$statement->fetchColumn();
    }

    $summary = [
        'source_file' => $sourceFile,
        'cpf' => $cpf,
        'users_found' => count($sourceUsers),
        'source_user_ids' => array_map('intval', array_column($sourceUsers, 'id')),
        'source_user_id' => $sourceUserId,
        'profiles_found' => array_values(array_unique(array_map(
            static fn(array $user): string => (string)($user['profile_type'] ?? 'clt'),
            $sourceUsers
        ))),
        'profile_override' => $profile,
        'rows_by_table' => $tableReport,
        'executed' => false,
        'inserted_by_table' => [],
        'ignored_existing_by_table' => [],
        'message' => 'Previa concluida. Nenhum dado foi alterado.',
    ];
    if (count($sourceUsers) > 1) {
        $summary['warning'] = 'CPF duplicado na origem; selecione um source-user-id antes de executar.';
    }

    if (!$execute) {
        return $summary;
    }

    if (!function_exists('sf_database_config') || !function_exists('sf_schema_mysql')) {
        throw new RuntimeException('Bootstrap do sistema nao foi carregado.');
    }
    $config = sf_database_config();
    if ($config['driver'] !== 'mysql') {
        throw new RuntimeException('A migracao exige driver mysql no config/config.local.php.');
    }
    if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
        throw new RuntimeException('A extensao PDO MySQL nao esta habilitada.');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );
    $target = new PDO($dsn, (string)$config['username'], (string)$config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => true,
    ]);
    $target->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
    sf_schema_mysql($target);

    $targetTables = array_map('strval', $target->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN));
    $targetTableMap = array_fill_keys($targetTables, true);
    $sourceToTargetUsers = [];
    $target->beginTransaction();

    try {
        $target->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($sourceUsers as $sourceUser) {
            $sourceId = (int)$sourceUser['id'];
            $targetId = sf_legacy_find_target_user($target, $sourceUser);
            if ($targetId === null) {
                $userRow = $sourceUser;
                unset($userRow['id']);
                $userRow = sf_legacy_apply_profile($userRow, $profile);
                $targetId = sf_legacy_insert_row($target, 'users', $userRow);
                $summary['inserted_by_table']['users'] = ($summary['inserted_by_table']['users'] ?? 0) + 1;
            } else {
                if ($profile !== 'keep') {
                    sf_legacy_update_profile($target, $targetId, $profile);
                    $summary['profile_updated_user_ids'][] = $targetId;
                }
                $summary['ignored_existing_by_table']['users'] = ($summary['ignored_existing_by_table']['users'] ?? 0) + 1;
            }
            $sourceToTargetUsers[$sourceId] = $targetId;
        }

        $orderedTables = sf_legacy_order_tables($sourceTables);
        foreach ($orderedTables as $table) {
            $table = (string)$table;
            if ($table === 'users' || !isset($targetTableMap[$table])) {
                continue;
            }
            $columns = array_column($source->query('PRAGMA table_info(' . sf_legacy_quote($table) . ')')->fetchAll(), 'name');
            if (!in_array('user_id', $columns, true)) {
                continue;
            }

            $ids = array_keys($sourceToTargetUsers);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $rowsStatement = $source->prepare(
                'SELECT * FROM ' . sf_legacy_quote($table) . ' WHERE user_id IN (' . $placeholders . ') ORDER BY id'
            );
            $rowsStatement->execute($ids);
            while ($row = $rowsStatement->fetch(PDO::FETCH_ASSOC)) {
                $sourceRowId = isset($row['id']) ? (int)$row['id'] : null;
                $row['user_id'] = $sourceToTargetUsers[(int)$row['user_id']];
                $row = sf_legacy_map_foreign_keys($target, $table, $row);

                $targetId = null;
                if ($sourceRowId !== null) {
                    $existing = $target->prepare('SELECT id FROM ' . sf_legacy_quote($table) . ' WHERE id = ?');
                    $existing->execute([$sourceRowId]);
                    $targetId = $existing->fetchColumn();
                }

                if ($targetId !== false && $targetId !== null) {
                    $summary['ignored_existing_by_table'][$table] = ($summary['ignored_existing_by_table'][$table] ?? 0) + 1;
                    continue;
                }

                $targetId = sf_legacy_insert_row($target, $table, $row);
                $summary['inserted_by_table'][$table] = ($summary['inserted_by_table'][$table] ?? 0) + 1;
            }
        }

        $target->exec('SET FOREIGN_KEY_CHECKS=1');
        $target->commit();
    } catch (Throwable $error) {
        if ($target->inTransaction()) {
            $target->rollBack();
        }
        throw $error;
    }

    $summary['executed'] = true;
    $summary['message'] = 'Migracao concluida sem apagar registros existentes.';
    return $summary;
}

function sf_legacy_source_users(PDO $source, string $cpf): array
{
    $columns = array_column($source->query('PRAGMA table_info("users")')->fetchAll(), 'name');
    $where = [];
    $params = [':cpf' => $cpf];
    if (in_array('cpf', $columns, true)) {
        $where[] = 'cpf = :cpf';
    }
    if (in_array('username', $columns, true)) {
        $where[] = 'username = :cpf';
    }
    if ($where === []) {
        throw new RuntimeException('A tabela users nao possui CPF ou username.');
    }
    $statement = $source->prepare('SELECT * FROM users WHERE ' . implode(' OR ', $where) . ' ORDER BY id');
    $statement->execute($params);
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function sf_legacy_find_target_user(PDO $target, array $sourceUser): ?int
{
    foreach (['cpf', 'username', 'email'] as $column) {
        $value = trim((string)($sourceUser[$column] ?? ''));
        if ($value === '') {
            continue;
        }
        $statement = $target->prepare(
            'SELECT id FROM users WHERE ' . sf_legacy_quote($column) . ' = ? ORDER BY id LIMIT 1'
        );
        $statement->execute([$value]);
        $id = $statement->fetchColumn();
        if ($id !== false) {
            return (int)$id;
        }
    }
    return null;
}

function sf_legacy_apply_profile(array $row, string $profile): array
{
    if ($profile === 'keep') {
        return $row;
    }
    $row['profile_type'] = $profile;
    if (array_key_exists('profile_completed', $row)) {
        $row['profile_completed'] = 1;
    }
    if ($profile === 'clt' && array_key_exists('profile_answers_json', $row)) {
        $row['profile_answers_json'] = json_encode([
            'mainActivity' => 'clt',
            'receivesSalary' => 'sim',
            'hasCnpj' => 'nao',
            'sellsProducts' => 'nao',
            'hasVehicles' => 'sim',
            'hasProperties' => 'nao',
            'taxControl' => 'nao',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    return $row;
}

function sf_legacy_update_profile(PDO $target, int $targetId, string $profile): void
{
    $row = sf_legacy_apply_profile(['profile_type' => $profile, 'profile_completed' => 1], $profile);
    $fields = ['profile_type = :profile_type', 'profile_completed = :profile_completed'];
    $parameters = [
        ':profile_type' => $row['profile_type'],
        ':profile_completed' => $row['profile_completed'],
        ':id' => $targetId,
    ];
    if ($profile === 'clt') {
        $fields[] = 'profile_answers_json = :profile_answers_json';
        $parameters[':profile_answers_json'] = json_encode([
            'mainActivity' => 'clt',
            'receivesSalary' => 'sim',
            'hasCnpj' => 'nao',
            'sellsProducts' => 'nao',
            'hasVehicles' => 'sim',
            'hasProperties' => 'nao',
            'taxControl' => 'nao',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    $statement = $target->prepare('UPDATE users SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id');
    $statement->execute($parameters);
}

function sf_legacy_map_foreign_keys(PDO $target, string $table, array $row): array
{
    foreach (['card_id' => 'cards', 'import_id' => 'imports'] as $column => $parentTable) {
        if (!array_key_exists($column, $row) || $row[$column] === null) {
            continue;
        }
        $statement = $target->prepare('SELECT id FROM ' . sf_legacy_quote($parentTable) . ' WHERE id = ?');
        $statement->execute([(int)$row[$column]]);
        $mapped = $statement->fetchColumn();
        if ($mapped !== false) {
            $row[$column] = $mapped;
        }
    }
    return $row;
}

function sf_legacy_insert_row(PDO $target, string $table, array $row): int
{
    $columns = array_keys($row);
    $columnSql = implode(', ', array_map('sf_legacy_quote', $columns));
    $placeholders = implode(', ', array_map(static fn(string $column): string => ':' . $column, $columns));
    $statement = $target->prepare(
        'INSERT IGNORE INTO ' . sf_legacy_quote($table)
        . ' (' . $columnSql . ') VALUES (' . $placeholders . ')'
    );
    $parameters = [];
    foreach ($row as $column => $value) {
        $parameters[':' . $column] = $value;
    }
    $statement->execute($parameters);
    return (int)$target->lastInsertId();
}

function sf_legacy_order_tables(array $tables): array
{
    $priority = [
        'cards' => 10,
        'imports' => 20,
        'bank_connections' => 30,
        'incomes' => 40,
        'goals' => 50,
        'professional_assets' => 60,
        'professional_taxes' => 61,
        'professional_clients' => 62,
        'professional_catalog' => 63,
        'professional_cash_accounts' => 64,
        'professional_categories' => 65,
        'professional_payment_methods' => 66,
        'professional_fiscal_documents' => 67,
        'professional_settings' => 68,
        'professional_permissions' => 69,
        'professional_schedule' => 70,
        'professional_credit_checks' => 71,
        'transactions' => 100,
    ];
    usort($tables, static fn($a, $b) => ($priority[$a] ?? 500) <=> ($priority[$b] ?? 500));
    return $tables;
}

function sf_legacy_quote(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}
