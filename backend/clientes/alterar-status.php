<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/functions.php';

exigirPermissao('CLIENTES_GERENCIAR');

$token = $_POST['csrf_token'] ?? null;
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrfToken(is_string($token) ? $token : null)) {
    flash('error', 'Requisição inválida ou sessão expirada.');
    header('Location: /clientes/index.php');
    exit;
}

try {
    $pdo = getDatabaseConnection();
    alterarStatusCliente($pdo, estruturaClientes($pdo), $_POST, (int) $_SESSION['empresa_id'], (int) $_SESSION['usuario_id']);
    flash('success', $_POST['ativo'] === '1' ? 'Cliente reativado com sucesso.' : 'Cliente inativado com sucesso.');
} catch (Throwable $erro) {
    if (!($erro instanceof InvalidArgumentException)) {
        error_log('Erro ao alterar status de cliente SGV9: ' . $erro->getMessage());
    }
    flash('error', $erro instanceof InvalidArgumentException ? $erro->getMessage() : 'Não foi possível alterar o status do cliente.');
}
header('Location: /clientes/index.php?status=todos');
exit;
