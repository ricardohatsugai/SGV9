<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/relacionados.php';
exigirPermissao('CLIENTES_GERENCIAR');
$token = $_POST['csrf_token'] ?? null;
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrfToken(is_string($token) ? $token : null)) {
    flash('error', 'Requisição inválida ou sessão expirada.');
    header('Location: /clientes/index.php'); exit;
}
$tipo = clienteTexto($_POST, 'tipo');
$config = clienteRelacionadoConfig($tipo);
$clienteId = clienteId($_POST['cliente_id'] ?? null);
$id = clienteId($_POST['id'] ?? null);
$ativo = $_POST['ativo'] ?? null;
if (!$config || $clienteId === null || $id === null || !in_array($ativo, ['0','1'], true)) {
    flash('error', 'Dados inválidos para alteração de status.');
    header('Location: /clientes/index.php'); exit;
}
$pdo = getDatabaseConnection();
try {
    $pdo->beginTransaction();
    if (!clienteConsultar($pdo, $clienteId, (int)$_SESSION['empresa_id'], true)) {
        throw new DomainException('Cliente não encontrado.');
    }
    $registro = clienteRelacionadoConsultar($pdo, $config, $id, $clienteId, true);
    if (!$registro) { throw new DomainException('Registro não encontrado neste cliente.'); }
    if ((int)$registro['ativo'] !== (int)$ativo) {
        if ($tipo === 'endereco' && $ativo === '1') {
            $bairro = clienteEnderecoLocalidade($pdo, (int)$registro['bairro_id']);
            if (!$bairro || !$bairro['localidade_ativa']) {
                throw new DomainException('Atualize o endereço para uma localidade ativa antes de reativá-lo.');
            }
            if ($registro['principal']) {
                $stmt = $pdo->prepare('SELECT id FROM enderecos_clientes WHERE cliente_id=:cliente_id AND ativo=1 AND principal=1 AND id<>:id');
                $stmt->execute(['cliente_id'=>$clienteId, 'id'=>$id]);
                if ($stmt->fetch()) {
                    throw new DomainException('Já existe outro endereço principal ativo. Edite este endereço para escolher qual será o principal.');
                }
            }
        }
        $pdo->prepare('UPDATE ' . $config['tabela'] . ' SET ativo=:ativo WHERE id=:id AND cliente_id=:cliente_id')
            ->execute(['ativo'=>$ativo, 'id'=>$id, 'cliente_id'=>$clienteId]);
        clienteAuditar($pdo, $clienteId, $config['acao'] . ($ativo === '1' ? '_REATIVADO' : '_INATIVADO'),
            ['registro_id'=>$id, 'ativo'=>(int)$registro['ativo']], ['registro_id'=>$id, 'ativo'=>(int)$ativo]);
    }
    $pdo->commit();
    flash('success', ucfirst($config['rotulo']) . ($ativo === '1' ? ' reativado com sucesso.' : ' inativado com sucesso.'));
} catch (Throwable $erro) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    if (!($erro instanceof DomainException)) { error_log('Erro ao alterar status de cliente: ' . $erro->getMessage()); }
    flash('error', $erro instanceof DomainException ? $erro->getMessage() : 'Não foi possível alterar o status do registro.');
}
header('Location: /clientes/detalhes.php?id=' . $clienteId . '&aba=' . $config['aba'] . '&status=todos'); exit;
