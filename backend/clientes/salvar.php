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

$id = ($_POST['id'] ?? null) === '0' ? 0 : clienteId($_POST['id'] ?? null);
if ($id === null || (isset($_POST['ativo']) && $_POST['ativo'] !== '1')) {
    flash('error', 'Dados inválidos para salvar o cliente.');
    header('Location: /clientes/index.php');
    exit;
}

$dados = clienteDados($_POST);
$destino = '/clientes/form.php' . ($id ? '?id=' . $id : '');
$_SESSION['cliente_form'] = ['id' => $id, 'dados' => $dados];
$erros = clienteValidar($dados);
foreach (array_keys(clientePadrao()) as $campo) {
    if (isset($_POST[$campo]) && !is_string($_POST[$campo])) {
        $erros[] = 'Formato inválido para o campo ' . str_replace('_', ' ', $campo) . '.';
    }
}
if ($erros) {
    flash('error', implode(' ', $erros));
    header('Location: ' . $destino);
    exit;
}

$pdo = getDatabaseConnection();
$empresaId = (int) $_SESSION['empresa_id'];
$dados['cnpj'] = formatarCnpj($dados['cnpj']);
$dados['limite_credito'] = str_replace(',', '.', $dados['limite_credito']);
foreach (['inscricao_estadual', 'inscricao_municipal', 'telefone', 'email', 'situacao_cadastral', 'observacao'] as $campo) {
    $dados[$campo] = $dados[$campo] === '' ? null : $dados[$campo];
}

try {
    $pdo->beginTransaction();
    $antes = $id ? clienteConsultar($pdo, $id, $empresaId, true) : null;
    if ($id && !$antes) {
        throw new DomainException('Cliente não encontrado.');
    }
    $subcategoria = $pdo->prepare('SELECT id FROM subcategorias_clientes WHERE id = :id FOR SHARE');
    $subcategoria->execute(['id' => $dados['subcategoria_id']]);
    if (!$subcategoria->fetchColumn()) {
        throw new DomainException('Selecione uma subcategoria existente.');
    }
    $duplicidade = $pdo->prepare('SELECT id FROM clientes WHERE empresa_id = :empresa_id AND cnpj = :cnpj AND id <> :id');
    $duplicidade->execute(['empresa_id' => $empresaId, 'cnpj' => $dados['cnpj'], 'id' => $id]);
    if ($duplicidade->fetchColumn()) {
        throw new DomainException('Já existe um cliente com esse CNPJ nesta empresa.');
    }

    $params = $dados + ['empresa_id' => $empresaId];
    if ($id) {
        $sql = 'UPDATE clientes SET subcategoria_id=:subcategoria_id, data_cadastro=:data_cadastro,
                nome_fantasia=:nome_fantasia, razao_social=:razao_social, cnpj=:cnpj,
                inscricao_estadual=:inscricao_estadual, inscricao_municipal=:inscricao_municipal,
                telefone=:telefone, email=:email, limite_credito=:limite_credito,
                situacao_cadastral=:situacao_cadastral, observacao=:observacao, ativo=:ativo
                WHERE id=:id AND empresa_id=:empresa_id';
        $params['id'] = $id;
    } else {
        $sql = 'INSERT INTO clientes
                (empresa_id,subcategoria_id,data_cadastro,nome_fantasia,razao_social,cnpj,
                 inscricao_estadual,inscricao_municipal,telefone,email,limite_credito,situacao_cadastral,observacao,ativo)
                VALUES (:empresa_id,:subcategoria_id,:data_cadastro,:nome_fantasia,:razao_social,:cnpj,
                 :inscricao_estadual,:inscricao_municipal,:telefone,:email,:limite_credito,:situacao_cadastral,:observacao,:ativo)';
    }
    $pdo->prepare($sql)->execute($params);
    $novoId = $id ?: (int) $pdo->lastInsertId();
    clienteAuditar($pdo, $novoId, $id ? 'CLIENTE_ATUALIZADO' : 'CLIENTE_CRIADO', $antes, $dados);
    $pdo->commit();
    unset($_SESSION['cliente_form']);
    flash('success', 'Cliente salvo com sucesso.');
    $destino = '/clientes/index.php?status=todos';
} catch (Throwable $erro) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($erro instanceof DomainException) {
        flash('error', $erro->getMessage());
    } else {
        error_log('Erro ao salvar cliente: ' . $erro->getMessage());
        flash('error', $erro instanceof PDOException && ($erro->errorInfo[1] ?? null) === 1062
            ? 'Já existe um cliente com esse CNPJ nesta empresa.' : 'Não foi possível salvar o cliente.');
    }
}
header('Location: ' . $destino);
exit;
