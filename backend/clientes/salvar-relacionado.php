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
$id = ($_POST['id'] ?? null) === '0' ? 0 : clienteId($_POST['id'] ?? null);
if (!$config || $clienteId === null || $id === null) {
    flash('error', 'Dados inválidos para salvar o registro.');
    header('Location: /clientes/index.php'); exit;
}
$destino = '/clientes/relacionado-form.php?tipo=' . $tipo . '&cliente_id=' . $clienteId . ($id ? '&id=' . $id : '');
$dados = clienteRelacionadoDados($_POST, $config, $tipo);
$_SESSION['cliente_relacionado_form'] = ['tipo'=>$tipo, 'cliente_id'=>$clienteId, 'id'=>$id, 'dados'=>$dados];
$erros = clienteRelacionadoValidar($_POST, $dados, $config, $tipo);
if ($erros) {
    flash('error', implode(' ', $erros));
    header('Location: ' . $destino); exit;
}
$pdo = getDatabaseConnection();
try {
    $pdo->beginTransaction();
    if (!clienteConsultar($pdo, $clienteId, (int)$_SESSION['empresa_id'], true)) {
        throw new DomainException('Cliente não encontrado.');
    }
    $antes = $id ? clienteRelacionadoConsultar($pdo, $config, $id, $clienteId, true) : null;
    if ($id && !$antes) {
        throw new DomainException('Registro não encontrado neste cliente.');
    }
    clienteRelacionadoReferencias($pdo, $dados, $tipo, $antes);
    $principalAnterior = null;
    if ($tipo === 'endereco') {
        $cep = somenteDigitos($dados['cep']);
        $dados['cep'] = substr($cep,0,5) . '-' . substr($cep,5,3);
        if ($dados['ativo'] && $dados['principal']) {
            $principalAnterior = clienteEnderecoTrocarPrincipal($pdo, $clienteId, $id);
        }
    } else {
        $stmt = $pdo->prepare('SELECT id, ativo FROM contatos_clientes
            WHERE cliente_id=:cliente_id AND departamento_id=:departamento_id AND nome=:nome AND id<>:id');
        $stmt->execute(['cliente_id'=>$clienteId, 'departamento_id'=>$dados['departamento_id'], 'nome'=>$dados['nome'], 'id'=>$id]);
        if ($stmt->fetch()) {
            throw new DomainException('Já existe um contato com este nome no departamento, inclusive entre os inativos. Edite ou reative o registro existente.');
        }
    }
    foreach ($dados as $campo=>$valor) {
        if ($valor === '' && !in_array($campo, ['logradouro', 'nome', 'cep'], true)) {
            $dados[$campo] = null;
        }
    }
    $params = $dados + ['cliente_id'=>$clienteId];
    if ($id) {
        $atribuicoes = array_map(static fn($campo)=>$campo . '=:' . $campo, array_keys($dados));
        $sql = 'UPDATE ' . $config['tabela'] . ' SET ' . implode(',', $atribuicoes) . ' WHERE id=:id AND cliente_id=:cliente_id';
        $params['id'] = $id;
    } else {
        $campos = array_keys($params);
        $sql = 'INSERT INTO ' . $config['tabela'] . ' (' . implode(',', $campos) . ') VALUES (:' . implode(',:', $campos) . ')';
    }
    $pdo->prepare($sql)->execute($params);
    $registroId = $id ?: (int)$pdo->lastInsertId();
    clienteAuditar($pdo, $clienteId, $config['acao'] . ($id ? '_ATUALIZADO' : '_CRIADO'),
        $antes ?: null, ['registro_id'=>$registroId, 'dados'=>$dados, 'principal_substituido'=>$principalAnterior]);
    $pdo->commit();
    unset($_SESSION['cliente_relacionado_form']);
    flash('success', ucfirst($config['rotulo']) . ' salvo com sucesso.');
    $destino = '/clientes/detalhes.php?id=' . $clienteId . '&aba=' . $config['aba'] . '&status=todos';
} catch (Throwable $erro) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    if ($erro instanceof DomainException) {
        flash('error', $erro->getMessage());
    } else {
        error_log('Erro ao salvar ' . $config['rotulo'] . ' de cliente: ' . $erro->getMessage());
        flash('error', $erro instanceof PDOException && ($erro->errorInfo[1] ?? null) === 1062
            ? 'Registro duplicado ou endereço principal em conflito.' : 'Não foi possível salvar o registro.');
    }
}
header('Location: ' . $destino); exit;
