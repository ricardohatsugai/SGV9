<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/functions.php';
exigirPermissao('CLIENTES_VISUALIZAR');

$pdo = getDatabaseConnection();
$busca = mb_substr(clienteTexto($_GET, 'q'), 0, 200);
$status = clienteTexto($_GET, 'status', 'ativos');
if (!in_array($status, ['ativos', 'inativos', 'todos'], true)) {
    $status = 'ativos';
}
$sql = 'SELECT c.*, s.nome AS subcategoria, cat.nome AS categoria
        FROM clientes c
        INNER JOIN subcategorias_clientes s ON s.id = c.subcategoria_id
        INNER JOIN categorias_clientes cat ON cat.id = s.categoria_id
        WHERE c.empresa_id = :empresa_id';
$params = ['empresa_id' => $_SESSION['empresa_id']];
if ($status !== 'todos') {
    $sql .= ' AND c.ativo = :ativo';
    $params['ativo'] = $status === 'ativos' ? 1 : 0;
}
if ($busca !== '') {
    $sql .= ' AND (c.nome_fantasia LIKE :fantasia OR c.razao_social LIKE :razao
              OR c.cnpj LIKE :cnpj OR c.email LIKE :email
              OR REPLACE(REPLACE(REPLACE(c.cnpj, ".", ""), "/", ""), "-", "") LIKE :documento)';
    foreach (['fantasia', 'razao', 'cnpj', 'email'] as $campo) {
        $params[$campo] = '%' . $busca . '%';
    }
    $digitos = somenteDigitos($busca);
    $params['documento'] = $digitos !== '' && preg_match('/^[0-9.\/-]+$/D', $busca)
        ? '%' . $digitos . '%' : '__sem_documento__';
}
$sql .= ' ORDER BY c.nome_fantasia, c.id';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll();
$gerenciar = usuarioTemPermissao('CLIENTES_GERENCIAR');

renderHeader('Clientes', 'Cadastro comercial dos clientes do escritório.');
?>
<div class="toolbar">
    <form method="get" class="search-form">
        <input type="search" name="q" maxlength="200" value="<?= e($busca) ?>" placeholder="Buscar por nome, CNPJ ou e-mail" aria-label="Buscar clientes">
        <select name="status" aria-label="Situação dos clientes">
            <option value="ativos" <?= $status === 'ativos' ? 'selected' : '' ?>>Ativos</option>
            <option value="inativos" <?= $status === 'inativos' ? 'selected' : '' ?>>Inativos</option>
            <option value="todos" <?= $status === 'todos' ? 'selected' : '' ?>>Todos</option>
        </select>
        <button class="button secondary">Buscar</button>
    </form>
    <?php if ($gerenciar): ?>
        <a href="/clientes/form.php" class="button primary">+ Novo cliente</a>
    <?php endif; ?>
</div>
<section class="panel table-panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Cliente</th><th>CNPJ</th><th>Categoria / Subcategoria</th><th>Contato</th><th>Status</th><th>Ações</th></tr></thead>
            <tbody>
            <?php if (!$clientes): ?>
                <tr><td colspan="6" class="empty">Nenhum cliente encontrado.</td></tr>
            <?php endif; ?>
            <?php foreach ($clientes as $cliente): ?>
                <tr>
                    <td><strong><?= e($cliente['nome_fantasia']) ?></strong><small><?= e($cliente['razao_social']) ?></small></td>
                    <td><?= e($cliente['cnpj']) ?></td>
                    <td><?= e($cliente['categoria']) ?><small><?= e($cliente['subcategoria']) ?></small></td>
                    <td><?= e($cliente['telefone'] ?? '—') ?><small><?= e($cliente['email'] ?? '') ?></small></td>
                    <td><span class="status <?= $cliente['ativo'] ? 'active' : 'inactive' ?>"><?= $cliente['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
                    <td class="actions">
                        <a href="/clientes/detalhes.php?id=<?= (int) $cliente['id'] ?>">Endereços e contatos</a>
                        <?php if ($gerenciar): ?>
                            <a href="/clientes/form.php?id=<?= (int) $cliente['id'] ?>">Editar</a>
                            <form action="/clientes/alterar-status.php" method="post" class="action-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int) $cliente['id'] ?>">
                                <input type="hidden" name="ativo" value="<?= $cliente['ativo'] ? '0' : '1' ?>">
                                <button type="submit" class="action-link" onclick="return confirm('Deseja <?= $cliente['ativo'] ? 'inativar' : 'reativar' ?> este cliente?')">
                                    <?= $cliente['ativo'] ? 'Inativar' : 'Reativar' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php renderFooter(); ?>
