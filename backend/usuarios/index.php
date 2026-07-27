<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../config/database.php';

exigirPermissao('USUARIOS_GERENCIAR');

$pdo = getDatabaseConnection();
$stmt = $pdo->prepare(
    'SELECT u.id, u.nome, u.email, u.ativo, u.ultimo_login_em, p.nome AS perfil
     FROM usuarios u
     INNER JOIN perfis p ON p.id = u.perfil_id
     WHERE u.empresa_id = :empresa_id
     ORDER BY u.nome'
);
$stmt->execute(['empresa_id' => $_SESSION['empresa_id']]);
$usuarios = $stmt->fetchAll();

renderHeader('Usuários', 'Usuários e níveis de acesso do escritório.');
?>
<div class="toolbar">
    <span></span>
    <a href="/usuarios/form.php" class="button primary">+ Novo usuário</a>
</div>
<section class="panel table-panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Usuário</th><th>Perfil</th><th>Último acesso</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><strong><?= e($u['nome']) ?></strong><small><?= e($u['email']) ?></small></td>
                    <td><?= e($u['perfil']) ?></td>
                    <td><?= $u['ultimo_login_em'] ? e(date('d/m/Y H:i', strtotime($u['ultimo_login_em']))) : 'Nunca acessou' ?></td>
                    <td><span class="status <?= $u['ativo'] ? 'active' : 'inactive' ?>"><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
                    <td class="actions"><a href="/usuarios/form.php?id=<?= (int) $u['id'] ?>">Editar</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php renderFooter(); ?>
