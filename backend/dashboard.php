<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/config/database.php';

$pdo = getDatabaseConnection();
$empresaId = (int) $_SESSION['empresa_id'];

function total(PDO $pdo, string $sql, int $empresaId): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['empresa_id' => $empresaId]);
    return (int) $stmt->fetchColumn();
}

$indicadores = [
    'representadas' => total($pdo, 'SELECT COUNT(*) FROM representadas WHERE empresa_id = :empresa_id AND ativo = 1', $empresaId),
    'clientes' => total($pdo, 'SELECT COUNT(*) FROM clientes WHERE empresa_id = :empresa_id AND ativo = 1', $empresaId),
    'vendedores' => total($pdo, 'SELECT COUNT(*) FROM vendedores WHERE empresa_id = :empresa_id AND ativo = 1', $empresaId),
    'pedidos' => total($pdo, 'SELECT COUNT(*) FROM pedidos WHERE empresa_id = :empresa_id', $empresaId),
];

renderHeader('Visão geral', 'Resumo inicial do escritório de representação.');
?>
<div class="metric-grid">
    <article class="metric-card"><span>Representadas</span><strong><?= $indicadores['representadas'] ?></strong><small>ativas</small></article>
    <article class="metric-card"><span>Clientes</span><strong><?= $indicadores['clientes'] ?></strong><small>ativos</small></article>
    <article class="metric-card"><span>Vendedores</span><strong><?= $indicadores['vendedores'] ?></strong><small>ativos</small></article>
    <article class="metric-card"><span>Pedidos</span><strong><?= $indicadores['pedidos'] ?></strong><small>registrados</small></article>
</div>

<section class="panel welcome-panel">
    <div>
        <p class="eyebrow">Próximo módulo</p>
        <h2>Cadastre suas representadas</h2>
        <p>As representadas serão utilizadas nos produtos, tabelas de preços, pedidos, faturamentos e comissões.</p>
    </div>
    <?php if (usuarioTemPermissao('REPRESENTADAS_GERENCIAR')): ?>
        <a href="/representadas/form.php" class="button primary">Nova representada</a>
    <?php endif; ?>
</section>
<?php renderFooter(); ?>
