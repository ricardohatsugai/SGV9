<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../config/database.php';

exigirPermissao('USUARIOS_GERENCIAR');

$pdo = getDatabaseConnection();
$id = (int) ($_GET['id'] ?? 0);
$usuario = ['id' => 0, 'nome' => '', 'email' => '', 'perfil_id' => '', 'ativo' => 1];

if ($id) {
    $stmt = $pdo->prepare('SELECT id,nome,email,perfil_id,ativo FROM usuarios WHERE id=:id AND empresa_id=:empresa_id');
    $stmt->execute(['id' => $id, 'empresa_id' => $_SESSION['empresa_id']]);
    $usuario = $stmt->fetch() ?: $usuario;
}

$perfis = $pdo->query('SELECT id,nome,descricao FROM perfis ORDER BY nome')->fetchAll();

renderHeader($id ? 'Editar usuário' : 'Novo usuário', 'Defina o perfil de acesso e as credenciais.');
?>
<form action="/usuarios/salvar.php" method="post" class="panel form-panel-admin narrow">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
    <div class="form-grid">
        <label class="field span-2">Nome
            <input name="nome" required maxlength="200" value="<?= e($usuario['nome']) ?>">
        </label>
        <label class="field span-2">E-mail
            <input type="email" name="email" required maxlength="200" value="<?= e($usuario['email']) ?>">
        </label>
        <label class="field">Perfil
            <select name="perfil_id" required>
                <option value="">Selecione</option>
                <?php foreach ($perfis as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= (string) $p['id'] === (string) $usuario['perfil_id'] ? 'selected' : '' ?>>
                        <?= e($p['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">Senha <?= $id ? '(deixe em branco para manter)' : '' ?>
            <input type="password" name="senha" <?= $id ? '' : 'required' ?> minlength="8">
        </label>
        <label class="check-field span-2">
            <input type="checkbox" name="ativo" value="1" <?= $usuario['ativo'] ? 'checked' : '' ?>>
            Usuário ativo
        </label>
    </div>
    <div class="form-actions">
        <a href="/usuarios/index.php" class="button secondary">Cancelar</a>
        <button class="button primary">Salvar usuário</button>
    </div>
</form>
<?php renderFooter(); ?>
