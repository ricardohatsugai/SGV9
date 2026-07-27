<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../config/database.php';

exigirPermissao('REPRESENTADAS_VISUALIZAR');

$pdo = getDatabaseConnection();
$busca = trim((string) ($_GET['q'] ?? ''));

$sql = 'SELECT r.id, r.nome, r.cnpj, r.telefone, r.email, r.ativo,
               c.nome AS cidade, e.sigla AS uf
        FROM representadas r
        LEFT JOIN bairros b ON b.id = r.bairro_id
        LEFT JOIN cidades c ON c.id = b.cidade_id
        LEFT JOIN estados e ON e.id = c.estado_id
        WHERE r.empresa_id = :empresa_id';

$params = ['empresa_id' => $_SESSION['empresa_id']];

if ($busca !== '') {
    $sql .= ' AND (r.nome LIKE :busca OR r.cnpj LIKE :busca)';
    $params['busca'] = '%' . $busca . '%';
}

$sql .= ' ORDER BY r.nome';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$representadas = $stmt->fetchAll();

renderHeader('Representadas', 'Empresas fabricantes ou fornecedoras representadas pelo escritório.');
?>
<div class="toolbar">
    <form method="get" class="search-form">
        <input type="search" name="q" value="<?= e($busca) ?>" placeholder="Buscar por nome ou CNPJ">
        <button class="button secondary">Buscar</button>
    </form>
    <?php if (usuarioTemPermissao('REPRESENTADAS_GERENCIAR')): ?>
        <a href="/representadas/form.php" class="button primary">+ Nova representada</a>
    <?php endif; ?>
</div>

<section class="panel table-panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Representada</th><th>CNPJ</th><th>Localização</th><th>Contato</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php if (!$representadas): ?>
                <tr><td colspan="6" class="empty">Nenhuma representada encontrada.</td></tr>
            <?php endif; ?>
            <?php foreach ($representadas as $r): ?>
                <tr>
                    <td><strong><?= e($r['nome']) ?></strong><small><?= e($r['email'] ?? '') ?></small></td>
                    <td><?= e($r['cnpj']) ?></td>
                    <td><?= e(trim(($r['cidade'] ?? '') . ' ' . ($r['uf'] ? '/ ' . $r['uf'] : ''))) ?></td>
                    <td><?= e($r['telefone'] ?? '—') ?></td>
                    <td><span class="status <?= $r['ativo'] ? 'active' : 'inactive' ?>"><?= $r['ativo'] ? 'Ativa' : 'Inativa' ?></span></td>
                    <td class="actions">
                        <?php if (usuarioTemPermissao('REPRESENTADAS_GERENCIAR')): ?>
                            <a href="/representadas/form.php?id=<?= (int) $r['id'] ?>">Editar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php renderFooter(); ?>
