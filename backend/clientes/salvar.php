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
    salvarCliente($pdo, estruturaClientes($pdo), $_POST, (int) $_SESSION['empresa_id'], (int) $_SESSION['usuario_id']);
    unset($_SESSION['cliente_form']);
    flash('success', 'Cliente salvo com sucesso.');
    header('Location: /clientes/index.php?status=todos');
} catch (Throwable $erro) {
    $mensagem = 'Não foi possível salvar o cliente.';
    if ($erro instanceof InvalidArgumentException) {
        $mensagem = $erro->getMessage();
    } else {
        error_log('Erro ao salvar cliente SGV9: ' . $erro->getMessage());
        if ($erro instanceof PDOException && ($erro->errorInfo[1] ?? null) === 1062) {
            $mensagem = 'Já existe um cliente com esse CNPJ nesta empresa.';
        }
    }
    $campos = ['id', 'versao', 'data_cadastro', 'nome_fantasia', 'razao_social', 'cnpj',
        'inscricao_estadual', 'inscricao_municipal', 'subcategoria_id', 'telefone', 'email',
        'limite_credito', 'observacao', 'ativo'];
    $anteriores = [];
    foreach ($campos as $campo) {
        if (isset($_POST[$campo]) && is_string($_POST[$campo])) {
            $anteriores[$campo] = mb_substr($_POST[$campo], 0, $campo === 'observacao' ? 16383 : 255, 'UTF-8');
        }
    }
    $anteriores['ativo'] = $anteriores['ativo'] ?? '0';
    $_SESSION['cliente_form'] = $anteriores;
    flash('error', $mensagem);
    $id = $anteriores['id'] ?? '0';
    header('Location: /clientes/form.php' . (ctype_digit($id) && (int) $id > 0 ? '?id=' . (int) $id : ''));
}
exit;
