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
$id = clienteId($_POST['id'] ?? null);
$ativo = $_POST['ativo'] ?? null;
if ($id === null || !in_array($ativo, ['0', '1'], true)) {
    flash('error', 'Dados inválidos para alteração de status.');
    header('Location: /clientes/index.php');
    exit;
}
$pdo = getDatabaseConnection();
try {
    $pdo->beginTransaction();
    $cliente = clienteConsultar($pdo, $id, (int) $_SESSION['empresa_id'], true);
    if (!$cliente) {
        throw new DomainException('Cliente não encontrado.');
    }
    if ((int) $cliente['ativo'] !== (int) $ativo) {
        $stmt = $pdo->prepare('UPDATE clientes SET ativo=:ativo WHERE id=:id AND empresa_id=:empresa_id');
        $stmt->execute(['ativo' => $ativo, 'id' => $id, 'empresa_id' => $_SESSION['empresa_id']]);
        clienteAuditar($pdo, $id, $ativo === '1' ? 'CLIENTE_REATIVADO' : 'CLIENTE_INATIVADO',
            ['ativo' => (int) $cliente['ativo']], ['ativo' => (int) $ativo]);
    }
    $pdo->commit();
    flash('success', $ativo === '1' ? 'Cliente reativado com sucesso.' : 'Cliente inativado com sucesso.');
} catch (Throwable $erro) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (!($erro instanceof DomainException)) {
        error_log('Erro ao alterar status do cliente: ' . $erro->getMessage());
    }
    flash('error', $erro instanceof DomainException ? $erro->getMessage() : 'Não foi possível alterar o status do cliente.');
}
header('Location: /clientes/index.php?status=todos');
exit;
