<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

exigirPermissao('USUARIOS_GERENCIAR');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrfToken($_POST['csrf_token'] ?? null)) {
    flash('error', 'Requisição inválida.');
    header('Location: /usuarios/index.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$nome = trim((string) ($_POST['nome'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$perfilId = (int) ($_POST['perfil_id'] ?? 0);
$senha = (string) ($_POST['senha'] ?? '');
$ativo = isset($_POST['ativo']) ? 1 : 0;

if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$perfilId || (!$id && strlen($senha) < 8)) {
    flash('error', 'Confira os dados informados. A senha deve ter ao menos 8 caracteres.');
    header('Location: /usuarios/form.php' . ($id ? '?id=' . $id : ''));
    exit;
}

$pdo = getDatabaseConnection();

try {
    if ($id) {
        $sql = 'UPDATE usuarios SET nome=:nome,email=:email,perfil_id=:perfil_id,ativo=:ativo';
        $params = compact('nome', 'email', 'perfilId', 'ativo');
        $params = ['nome'=>$nome,'email'=>$email,'perfil_id'=>$perfilId,'ativo'=>$ativo,'id'=>$id,'empresa_id'=>$_SESSION['empresa_id']];
        if ($senha !== '') {
            $sql .= ', senha=:senha';
            $params['senha'] = password_hash($senha, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id=:id AND empresa_id=:empresa_id';
    } else {
        $sql = 'INSERT INTO usuarios (empresa_id,perfil_id,nome,email,senha,ativo)
                VALUES (:empresa_id,:perfil_id,:nome,:email,:senha,:ativo)';
        $params = [
            'empresa_id'=>$_SESSION['empresa_id'],
            'perfil_id'=>$perfilId,
            'nome'=>$nome,
            'email'=>$email,
            'senha'=>password_hash($senha, PASSWORD_DEFAULT),
            'ativo'=>$ativo
        ];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    unset($_SESSION['permissoes']);
    flash('success', 'Usuário salvo com sucesso.');
} catch (PDOException $e) {
    error_log($e->getMessage());
    flash('error', $e->getCode() === '23000' ? 'Este e-mail já está cadastrado.' : 'Não foi possível salvar o usuário.');
}

header('Location: /usuarios/index.php');
exit;
