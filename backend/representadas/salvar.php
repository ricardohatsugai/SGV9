<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/validators.php';

exigirPermissao('REPRESENTADAS_GERENCIAR');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrfToken($_POST['csrf_token'] ?? null)) {
    flash('error', 'Requisição inválida ou sessão expirada.');
    header('Location: /representadas/index.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$nome = trim((string) ($_POST['nome'] ?? ''));
$cnpj = formatarCnpj(trim((string) ($_POST['cnpj'] ?? '')));

if (!validarCnpj($cnpj)) {
    flash('error', 'O CNPJ informado é inválido.');
    header('Location: /representadas/form.php' . ($id ? '?id=' . $id : ''));
    exit;
}

if ($nome === '' || $cnpj === '') {
    flash('error', 'Nome e CNPJ são obrigatórios.');
    header('Location: /representadas/form.php' . ($id ? '?id=' . $id : ''));
    exit;
}

$dados = [
    'empresa_id' => $_SESSION['empresa_id'],
    'nome' => $nome,
    'cnpj' => $cnpj,
    'inscricao_estadual' => trim((string) ($_POST['inscricao_estadual'] ?? '')) ?: null,
    'logradouro' => trim((string) ($_POST['logradouro'] ?? '')) ?: null,
    'numero' => trim((string) ($_POST['numero'] ?? '')) ?: null,
    'complemento' => trim((string) ($_POST['complemento'] ?? '')) ?: null,
    'bairro_id' => ($_POST['bairro_id'] ?? '') !== '' ? (int) $_POST['bairro_id'] : null,
    'cep' => trim((string) ($_POST['cep'] ?? '')) ?: null,
    'telefone' => trim((string) ($_POST['telefone'] ?? '')) ?: null,
    'email' => trim((string) ($_POST['email'] ?? '')) ?: null,
    'ativo' => isset($_POST['ativo']) ? 1 : 0,
    'observacao' => trim((string) ($_POST['observacao'] ?? '')) ?: null,
];

$pdo = getDatabaseConnection();

try {
    $pdo->beginTransaction();

    if ($id > 0) {
        $dados['id'] = $id;
        $sql = 'UPDATE representadas SET
                    nome=:nome, cnpj=:cnpj, inscricao_estadual=:inscricao_estadual,
                    logradouro=:logradouro, numero=:numero, complemento=:complemento,
                    bairro_id=:bairro_id, cep=:cep, telefone=:telefone, email=:email,
                    ativo=:ativo, observacao=:observacao
                WHERE id=:id AND empresa_id=:empresa_id';
        $acao = 'REPRESENTADA_ATUALIZADA';
    } else {
        $sql = 'INSERT INTO representadas
                (empresa_id,nome,cnpj,inscricao_estadual,logradouro,numero,complemento,
                 bairro_id,cep,telefone,email,ativo,observacao)
                VALUES
                (:empresa_id,:nome,:cnpj,:inscricao_estadual,:logradouro,:numero,:complemento,
                 :bairro_id,:cep,:telefone,:email,:ativo,:observacao)';
        $acao = 'REPRESENTADA_CRIADA';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($dados);

    if ($id === 0) {
        $id = (int) $pdo->lastInsertId();
    }

    $log = $pdo->prepare(
        'INSERT INTO logs_usuarios
        (empresa_id, usuario_id, acao, entidade, registro_id, dados_novos, endereco_ip, user_agent)
        VALUES (:empresa_id, :usuario_id, :acao, "representadas", :registro_id, :dados_novos, :ip, :user_agent)'
    );
    $log->execute([
        'empresa_id' => $_SESSION['empresa_id'],
        'usuario_id' => $_SESSION['usuario_id'],
        'acao' => $acao,
        'registro_id' => (string) $id,
        'dados_novos' => json_encode($dados, JSON_UNESCAPED_UNICODE),
        'ip' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
    ]);

    $pdo->commit();
    flash('success', 'Representada salva com sucesso.');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    flash('error', $e->getCode() === '23000'
        ? 'Já existe uma representada com esse CNPJ.'
        : 'Não foi possível salvar a representada.');
}

header('Location: /representadas/index.php');
exit;
