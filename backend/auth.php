<?php
declare(strict_types=1);

require_once __DIR__ . '/config/session.php';

startSecureSession();

if (empty($_SESSION['usuario_id'])) {
    $_SESSION['login_error'] = 'Faça login para acessar o sistema.';
    header('Location: /');
    exit;
}

$tempoMaximoInatividade = 60 * 60 * 4;
$ultimaAtividade = (int) ($_SESSION['ultima_atividade'] ?? time());

if ((time() - $ultimaAtividade) > $tempoMaximoInatividade) {
    session_unset();
    session_destroy();

    startSecureSession();
    $_SESSION['login_error'] = 'Sua sessão expirou. Entre novamente.';
    header('Location: /');
    exit;
}

$_SESSION['ultima_atividade'] = time();
