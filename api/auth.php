<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

try {
    $pdo = sf_db();
    sf_start_session();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $user = sf_current_user($pdo);
        $action = sf_text($_GET['action'] ?? '', 40);
        if ($action === 'admin_users') {
            sf_require_admin($pdo);
            sf_json(['ok' => true, 'users' => sf_admin_users($pdo)]);
        }
        sf_json([
            'ok' => true,
            'authenticated' => (bool)$user,
            'user' => $user ? sf_user_payload($user) : null,
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sf_json(['ok' => false, 'message' => 'Metodo nao permitido.'], 405);
    }

    $input = sf_input();
    $action = sf_text($input['action'] ?? 'login', 40);

    if ($action === 'logout') {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
        }
        session_destroy();
        sf_json(['ok' => true, 'message' => 'Sessao encerrada.']);
    }

    if ($action === 'change_password') {
        $user = sf_current_user($pdo);
        if (!$user) {
            sf_json(['ok' => false, 'message' => 'Sessao expirada. Faca login novamente.'], 401);
        }
        $currentPassword = (string)($input['currentPassword'] ?? '');
        $newPassword = (string)($input['newPassword'] ?? '');
        if (!password_verify($currentPassword, (string)$user['password_hash'])) {
            sf_json(['ok' => false, 'message' => 'A senha atual nao confere.'], 422);
        }
        if (strlen($newPassword) < 8) {
            sf_json(['ok' => false, 'message' => 'A nova senha deve ter pelo menos 8 caracteres.'], 422);
        }
        $stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash, must_change_password = 0, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            ':updated_at' => sf_now(),
            ':id' => (int)$user['id'],
        ]);
        sf_json(['ok' => true, 'message' => 'Senha atualizada com sucesso.', 'user' => sf_user_payload(sf_current_user($pdo))]);
    }

    if ($action === 'create_user') {
        sf_require_admin($pdo);
        $name = sf_text($input['name'] ?? '', 160);
        $email = strtolower(sf_text($input['email'] ?? '', 190));
        $username = strtolower(sf_text($input['username'] ?? '', 50));
        $cpf = preg_replace('/\D+/', '', (string)($input['cpf'] ?? ''));
        $password = (string)($input['password'] ?? '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sf_json(['ok' => false, 'message' => 'Informe nome e e-mail validos.'], 422);
        }
        if (!preg_match('/^[a-z0-9][a-z0-9._-]{2,49}$/', $username)) {
            sf_json(['ok' => false, 'message' => 'O usuario deve ter de 3 a 50 caracteres, usando letras, numeros, ponto, hifen ou sublinhado.'], 422);
        }
        if (strlen($password) < 8) {
            sf_json(['ok' => false, 'message' => 'A senha inicial deve ter pelo menos 8 caracteres.'], 422);
        }
        if ($cpf !== '' && strlen($cpf) !== 11) {
            sf_json(['ok' => false, 'message' => 'O CPF informado deve ter 11 digitos.'], 422);
        }

        $duplicate = $pdo->prepare('SELECT id FROM users WHERE lower(email) = :email OR lower(username) = :username OR (:cpf_check <> \'\' AND cpf = :cpf_value) LIMIT 1');
        $duplicate->execute([':email' => $email, ':username' => $username, ':cpf_check' => $cpf, ':cpf_value' => $cpf]);
        if ($duplicate->fetch()) {
            sf_json(['ok' => false, 'message' => 'Ja existe um usuario com este e-mail, nome de usuario ou CPF.'], 409);
        }

        $now = sf_now();
        $stmt = $pdo->prepare('
            INSERT INTO users (name, email, cpf, username, profile_type, profile_completed, profile_answers_json, password_hash, is_admin, must_change_password, created_at, updated_at)
            VALUES (:name, :email, :cpf, :username, \'clt\', 0, \'{}\', :password_hash, 0, 1, :created_at, :updated_at)
        ');
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':cpf' => $cpf,
            ':username' => $username,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
        $userId = (int)$pdo->lastInsertId();
        $created = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $created->execute([':id' => $userId]);
        sf_json(['ok' => true, 'message' => 'Usuario criado e autorizado pelo administrador.', 'user' => sf_admin_user_payload($created->fetch() ?: [])]);
    }

    if ($action === 'register') {
        sf_json(['ok' => false, 'message' => 'Novos acessos só podem ser criados e autorizados pelo administrador.'], 403);
    }

    if ($action === 'save_profile') {
        $user = sf_current_user($pdo);
        if (!$user) {
            sf_json(['ok' => false, 'message' => 'Sessao expirada. Faca login novamente.'], 401);
        }
        $name = sf_text($input['name'] ?? $user['name'], 160);
        $initialSetup = !empty($input['initialSetup']) || empty($user['profile_completed']);
        $answers = is_array($input['answers'] ?? null) ? $input['answers'] : [];
        $profileType = $initialSetup ? sf_infer_profile_type($answers) : sf_profile_type($user['profile_type'] ?? 'clt');
        $answersJson = $initialSetup
            ? json_encode($answers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : (string)($user['profile_answers_json'] ?? '{}');
        if ($name === '') {
            sf_json(['ok' => false, 'message' => 'Informe seu nome.'], 422);
        }
        $stmt = $pdo->prepare('UPDATE users SET name = :name, profile_type = :profile_type, profile_completed = 1, profile_answers_json = :profile_answers_json, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            ':id' => (int)$user['id'],
            ':name' => $name,
            ':profile_type' => $profileType,
            ':profile_answers_json' => $answersJson ?: '{}',
            ':updated_at' => sf_now(),
        ]);
        sf_json([
            'ok' => true,
            'message' => $initialSetup ? 'Perfil identificado e dashboard configurado.' : 'Perfil atualizado com sucesso.',
            'user' => sf_user_payload(sf_current_user($pdo)),
        ]);
    }

    $identity = strtolower(sf_text($input['identity'] ?? $input['email'] ?? '', 190));
    $password = (string)($input['password'] ?? $input['senha'] ?? '');

    if ($identity === '' || $password === '') {
        sf_json(['ok' => false, 'message' => 'Informe e-mail/CPF e senha.'], 422);
    }

    $identityCpf = preg_replace('/\D+/', '', $identity);
    $stmt = $pdo->prepare('SELECT * FROM users WHERE lower(email) = :email OR lower(username) = :username OR cpf = :cpf LIMIT 1');
    $stmt->execute([
        ':email' => $identity,
        ':username' => $identity,
        ':cpf' => $identityCpf,
    ]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, (string)$user['password_hash'])) {
        sf_json(['ok' => false, 'message' => 'Login ou senha invalidos.'], 401);
    }

    session_regenerate_id(true);
    $_SESSION['sf_user_id'] = (int)$user['id'];
    sf_json([
        'ok' => true,
        'message' => 'Login realizado.',
        'user' => sf_user_payload($user),
    ]);
} catch (Throwable $error) {
    sf_json([
        'ok' => false,
        'message' => 'Falha na autenticacao: ' . $error->getMessage(),
    ], 500);
}
