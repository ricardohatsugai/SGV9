<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/functions.php';

exigirPermissao('CLIENTES_VISUALIZAR');

try {
    $busca = mb_substr(entradaCliente($_GET, 'q'), 0, 200, 'UTF-8');
    $status = entradaCliente($_GET, 'status', 'ativos');
    $paginaTexto = entradaCliente($_GET, 'pagina', '1');
} catch (InvalidArgumentException $erro) {
    http_response_code(400);
    exit('Parâmetros de pesquisa inválidos.');
}
if (!in_array($status, ['ativos', 'inativos', 'todos'], true)) {
    $status = 'ativos';
}
$pagina = ctype_digit($paginaTexto) ? max(1, min(1000000, (int) $paginaTexto)) : 1;
$clientes = [];
$total = 0;
$paginas = 1;
$erroConsulta = false;
$gerenciar = usuarioTemPermissao('CLIENTES_GERENCIAR');

try {
    $pdo = getDatabaseConnection();
    $estrutura = estruturaClientes($pdo);
    $campos = $estrutura['campos'];
    $where = ' WHERE c.empresa_id = :empresa_id';
    $params = ['empresa_id' => (int) $_SESSION['empresa_id']];
    if ($status !== 'todos') {
        $where .= ' AND c.ativo = :ativo';
        $params['ativo'] = $status === 'ativos' ? 1 : 0;
    }
    if ($busca !== '') {
        $where .= ' AND (c.`' . $campos['nome_fantasia'] . '` LIKE :nome
            OR c.`' . $campos['razao_social'] . '` LIKE :razao OR c.email LIKE :email
            OR c.cnpj LIKE :cnpj OR CAST(c.id AS CHAR) = :codigo';
        foreach (['nome', 'razao', 'email', 'cnpj'] as $param) {
            $params[$param] = '%' . $busca . '%';
        }
        $params['codigo'] = $busca;
        if (preg_match('/^[0-9.\/-]+$/D', $busca)) {
            $where .= " OR REPLACE(REPLACE(REPLACE(c.cnpj, '.', ''), '/', ''), '-', '') LIKE :documento";
            $params['documento'] = '%' . somenteDigitos($busca) . '%';
        }
        $where .= ')';
    }
    $contagem = $pdo->prepare('SELECT COUNT(*) FROM clientes c' . $where);
    $contagem->execute($params);
    $total = (int) $contagem->fetchColumn();
    $paginas = max(1, (int) ceil($total / 25));
    $pagina = min($pagina, $paginas);
    $consulta = $pdo->prepare(
        'SELECT c.*, s.nome AS classificacao, cat.nome AS categoria FROM clientes c
         INNER JOIN `' . $estrutura['subcategorias'] . '` s ON s.id = c.`' . $campos['subcategoria_id'] . '`
         INNER JOIN `' . $estrutura['categorias'] . '` cat ON cat.id = s.categoria_id'
        . $where . ' ORDER BY c.`' . $campos['nome_fantasia'] . '`, c.id LIMIT :limite OFFSET :inicio'
    );
    foreach ($params as $param => $valor) {
        $consulta->bindValue(':' . $param, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $consulta->bindValue(':limite', 25, PDO::PARAM_INT);
    $consulta->bindValue(':inicio', ($pagina - 1) * 25, PDO::PARAM_INT);
    $consulta->execute();
    $clientes = $consulta->fetchAll();
    foreach ($clientes as &$cliente) {
        foreach ($campos as $campo => $coluna) {
            $cliente[$campo] = $cliente[$coluna];
        }
        $original = $cliente;
        unset($original['classificacao'], $original['categoria']);
        $cliente['versao'] = versaoCliente($original);
    }
    unset($cliente);
} catch (Throwable $erro) {
    error_log('Erro ao listar clientes SGV9: ' . $erro->getMessage());
    $erroConsulta = true;
    http_response_code(500);
}

renderHeader('Clientes', 'Cadastro e consulta dos clientes do escritório.');
?>
<div class="toolbar">
    <form method="get" class="search-form">
        <input type="search" name="q" maxlength="200" value="<?= e($busca) ?>" placeholder="Código, nome, razão social, CNPJ ou e-mail" aria-label="Buscar clientes">
        <select name="status" aria-label="Situação do cliente">
            <option value="ativos" <?= $status === 'ativos' ? 'selected' : '' ?>>Ativos</option>
            <option value="inativos" <?= $status === 'inativos' ? 'selected' : '' ?>>Inativos</option>
            <option value="todos" <?= $status === 'todos' ? 'selected' : '' ?>>Todos</option>
        </select>
        <button class="button secondary">Buscar</button>
    </form>
    <?php if ($gerenciar): ?><a href="/clientes/form.php" class="button primary">+ Novo cliente</a><?php endif; ?>
</div>
<?php if ($erroConsulta): ?><div class="flash error">Não foi possível consultar os clientes. Tente novamente.</div><?php endif; ?>
<section class="panel table-panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Cliente</th><th>CNPJ</th><th>Classificação</th><th>Contato</th><th>Status</th><th>Ações</th></tr></thead>
            <tbody>
            <?php if (!$clientes && !$erroConsulta): ?><tr><td colspan="6" class="empty">Nenhum cliente encontrado.</td></tr><?php endif; ?>
            <?php foreach ($clientes as $cliente): ?>
                <tr>
                    <td><strong><?= e($cliente['nome_fantasia']) ?></strong><small>#<?= (int) $cliente['id'] ?> · <?= e($cliente['razao_social']) ?></small></td>
                    <td><?= e($cliente['cnpj']) ?></td>
                    <td><?= e($cliente['categoria']) ?><small><?= e($cliente['classificacao']) ?></small></td>
                    <td><?= e($cliente['telefone'] ?? '') ?><small><?= e($cliente['email'] ?? '') ?></small></td>
                    <td><span class="status <?= $cliente['ativo'] ? 'active' : 'inactive' ?>"><?= $cliente['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
                    <td class="actions">
                        <?php if ($gerenciar): ?>
                            <a href="/clientes/form.php?id=<?= (int) $cliente['id'] ?>">Editar</a>
                            <form action="/clientes/alterar-status.php" method="post" class="action-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int) $cliente['id'] ?>">
                                <input type="hidden" name="versao" value="<?= e($cliente['versao']) ?>">
                                <input type="hidden" name="ativo" value="<?= $cliente['ativo'] ? '0' : '1' ?>">
                                <button class="action-link" onclick="return confirm('Deseja <?= $cliente['ativo'] ? 'inativar' : 'reativar' ?> este cliente?')"><?= $cliente['ativo'] ? 'Inativar' : 'Reativar' ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php if (!$erroConsulta): ?>
    <div class="toolbar">
        <p><?= $total ?> cliente(s) · Página <?= $pagina ?> de <?= $paginas ?></p>
        <nav aria-label="Páginas de clientes">
            <?php if ($pagina > 1): ?><a class="button secondary" href="?<?= e(http_build_query(['q' => $busca, 'status' => $status, 'pagina' => $pagina - 1])) ?>">Anterior</a><?php endif; ?>
            <?php if ($pagina < $paginas): ?><a class="button secondary" href="?<?= e(http_build_query(['q' => $busca, 'status' => $status, 'pagina' => $pagina + 1])) ?>">Próxima</a><?php endif; ?>
        </nav>
    </div>
<?php endif; ?>
<?php renderFooter(); ?>
