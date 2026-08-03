<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../config/database.php';

exigirPermissao('REPRESENTADAS_VISUALIZAR');

$pdo = getDatabaseConnection();

$busca = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? 'ativas');

$statusPermitidos = ['ativas', 'inativas', 'todas'];

if (!in_array($status, $statusPermitidos, true)) {
    $status = 'ativas';
}

$sql = 'SELECT
            r.id,
            r.nome,
            r.cnpj,
            r.telefone,
            r.email,
            r.ativo,
            c.nome AS cidade,
            e.sigla AS uf
        FROM representadas r
        LEFT JOIN bairros b ON b.id = r.bairro_id
        LEFT JOIN cidades c ON c.id = b.cidade_id
        LEFT JOIN estados e ON e.id = c.estado_id
        WHERE r.empresa_id = :empresa_id';

$params = [
    'empresa_id' => $_SESSION['empresa_id'],
];

if ($status === 'ativas') {
    $sql .= ' AND r.ativo = 1';
} elseif ($status === 'inativas') {
    $sql .= ' AND r.ativo = 0';
}

if ($busca !== '') {
    $sql .= ' AND (
        r.nome LIKE :busca
        OR r.cnpj LIKE :busca
        OR r.email LIKE :busca
    )';

    $params['busca'] = '%' . $busca . '%';
}

$sql .= ' ORDER BY r.nome';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$representadas = $stmt->fetchAll();

renderHeader(
    'Representadas',
    'Empresas fabricantes ou fornecedoras representadas pelo escritório.'
);
?>

<div class="toolbar">
    <form method="get" class="search-form">
        <input
            type="search"
            name="q"
            value="<?= e($busca) ?>"
            placeholder="Buscar por nome, CNPJ ou e-mail"
        >

        <select name="status">
            <option value="ativas" <?= $status === 'ativas' ? 'selected' : '' ?>>
                Ativas
            </option>

            <option value="inativas" <?= $status === 'inativas' ? 'selected' : '' ?>>
                Inativas
            </option>

            <option value="todas" <?= $status === 'todas' ? 'selected' : '' ?>>
                Todas
            </option>
        </select>

        <button class="button secondary">Buscar</button>
    </form>

    <?php if (usuarioTemPermissao('REPRESENTADAS_GERENCIAR')): ?>
        <a href="/representadas/form.php" class="button primary">
            + Nova representada
        </a>
    <?php endif; ?>
</div>

<section class="panel table-panel">
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Representada</th>
                <th>CNPJ</th>
                <th>Localização</th>
                <th>Contato</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>

            <tbody>
            <?php if (!$representadas): ?>
                <tr>
                    <td colspan="6" class="empty">
                        Nenhuma representada encontrada.
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($representadas as $representada): ?>
                <tr>
                    <td>
                        <strong><?= e($representada['nome']) ?></strong>
                        <small><?= e($representada['email'] ?? '') ?></small>
                    </td>

                    <td><?= e($representada['cnpj']) ?></td>

                    <td>
                        <?= e(
                            trim(
                                ($representada['cidade'] ?? '')
                                . (
                                    !empty($representada['uf'])
                                        ? ' / ' . $representada['uf']
                                        : ''
                                )
                            )
                        ) ?>
                    </td>

                    <td><?= e($representada['telefone'] ?? '—') ?></td>

                    <td>
                        <span class="status <?= $representada['ativo'] ? 'active' : 'inactive' ?>">
                            <?= $representada['ativo'] ? 'Ativa' : 'Inativa' ?>
                        </span>
                    </td>

                    <td class="actions">
                        <?php if (usuarioTemPermissao('REPRESENTADAS_GERENCIAR')): ?>

                            <a href="/representadas/form.php?id=<?= (int) $representada['id'] ?>">
                                Editar
                            </a>

                            <form
                                action="/representadas/alterar-status.php"
                                method="post"
                                class="action-form"
                            >
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(csrfToken()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) $representada['id'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="ativo"
                                    value="<?= $representada['ativo'] ? '0' : '1' ?>"
                                >

                                <button
                                    type="submit"
                                    class="action-link"
                                    onclick="return confirm(
                                        'Deseja <?= $representada['ativo'] ? 'inativar' : 'reativar' ?> esta representada?'
                                    )"
                                >
                                    <?= $representada['ativo'] ? 'Inativar' : 'Reativar' ?>
                                </button>
                            </form>

                            <form
                                action="/representadas/excluir.php"
                                method="post"
                                class="action-form"
                            >
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(csrfToken()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) $representada['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    class="action-link danger-link"
                                    onclick="return confirm(
                                        'Esta exclusão é definitiva. Deseja continuar?'
                                    )"
                                >
                                    Excluir
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