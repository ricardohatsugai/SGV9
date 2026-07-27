<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

startSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$senha = (string) ($_POST['senha'] ?? '');
$csrfToken = $_POST['csrf_token'] ?? null;

$_SESSION['old_email'] = $email;

if (!validateCsrfToken(is_string($csrfToken) ? $csrfToken : null)) {
    $_SESSION['login_error'] = 'A sessão expirou. Atualize a página e tente novamente.';
    header('Location: /');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
    $_SESSION['login_error'] = 'Informe um e-mail válido e sua senha.';
    header('Location: /');
    exit;
}

try {
    $pdo = getDatabaseConnection();

    $sql = <<<'SQL'
        SELECT
            u.id,
            u.empresa_id,
            u.perfil_id,
            u.nome,
            u.email,
            u.senha,
            u.ativo,
            e.nome_fantasia AS empresa_nome,
            e.ativo AS empresa_ativa,
            p.nome AS perfil_nome
        FROM usuarios u
        INNER JOIN empresas e ON e.id = u.empresa_id
        INNER JOIN perfis p ON p.id = u.perfil_id
        WHERE u.email = :email
        LIMIT 1
    SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    $credenciaisValidas = $usuario
        && (bool) $usuario['ativo']
        && (bool) $usuario['empresa_ativa']
        && password_verify($senha, $usuario['senha']);

    if (!$credenciaisValidas) {
        $_SESSION['login_error'] = 'E-mail ou senha inválidos.';
        usleep(350000);
        header('Location: /');
        exit;
    }

    session_regenerate_id(true);

    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['empresa_id'] = (int) $usuario['empresa_id'];
    $_SESSION['perfil_id'] = (int) $usuario['perfil_id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['empresa_nome'] = $usuario['empresa_nome'];
    $_SESSION['perfil_nome'] = $usuario['perfil_nome'];
    $_SESSION['autenticado_em'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $update = $pdo->prepare(
        'UPDATE usuarios SET ultimo_login_em = CURRENT_TIMESTAMP WHERE id = :id'
    );
    $update->execute(['id' => $usuario['id']]);

    $log = $pdo->prepare(
        'INSERT INTO logs_usuarios
         (empresa_id, usuario_id, acao, entidade, registro_id, endereco_ip, user_agent)
         VALUES (:empresa_id, :usuario_id, :acao, :entidade, :registro_id, :ip, :user_agent)'
    );
    $log->execute([
        'empresa_id' => $usuario['empresa_id'],
        'usuario_id' => $usuario['id'],
        'acao' => 'LOGIN_REALIZADO',
        'entidade' => 'usuarios',
        'registro_id' => (string) $usuario['id'],
        'ip' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
    ]);

    unset($_SESSION['old_email']);

    if (isset($_POST['lembrar'])) {
        setcookie('sgv9_email', $email, [
            'expires' => time() + (60 * 60 * 24 * 30),
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
    } else {
        setcookie('sgv9_email', '', time() - 3600, '/');
    }

    header('Location: /dashboard.php');
    exit;
} catch (PDOException $exception) {
    error_log('Erro de autenticação SGV9: ' . $exception->getMessage());
    $_SESSION['login_error'] = 'Não foi possível acessar o sistema. Tente novamente.';
    header('Location: /');
    exit;
}
