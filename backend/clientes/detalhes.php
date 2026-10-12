<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/relacionados.php';
exigirPermissao('CLIENTES_VISUALIZAR');
$id = clienteId($_GET['id'] ?? null);
if ($id === null) { http_response_code(400); exit('Identificador de cliente inválido.'); }
$pdo = getDatabaseConnection();
$cliente = clienteConsultar($pdo, $id, (int)$_SESSION['empresa_id']);
if (!$cliente) { http_response_code(404); exit('Cliente não encontrado.'); }
$aba = clienteTexto($_GET,'aba','enderecos');
$tipo = $aba === 'contatos' ? 'contato' : 'endereco';
$config = clienteRelacionadoConfig($tipo);
$aba = $config['aba'];
$status = clienteTexto($_GET,'status','ativos');
if (!in_array($status,['ativos','inativos','todos'],true)) { $status='ativos'; }
$sql = $tipo === 'endereco'
    ? 'SELECT r.*, t.tipo AS classificacao, b.nome AS bairro, c.nome AS cidade, e.sigla AS uf,
       (b.ativo=1 AND c.ativo=1 AND e.ativo=1) AS localidade_ativa
       FROM enderecos_clientes r JOIN tipos_endereco t ON t.id=r.tipo_endereco_id
       JOIN bairros b ON b.id=r.bairro_id JOIN cidades c ON c.id=b.cidade_id JOIN estados e ON e.id=c.estado_id'
    : 'SELECT r.*, d.nome AS classificacao FROM contatos_clientes r JOIN departamentos_clientes d ON d.id=r.departamento_id';
$sql .= ' WHERE r.cliente_id=:cliente_id';
$params = ['cliente_id'=>$id];
if ($status !== 'todos') { $sql .= ' AND r.ativo=:ativo'; $params['ativo']=$status === 'ativos' ? 1 : 0; }
$sql .= $tipo === 'endereco' ? ' ORDER BY r.ativo DESC,r.principal DESC,r.id' : ' ORDER BY d.nome,r.nome,r.id';
$stmt = $pdo->prepare($sql); $stmt->execute($params); $registros=$stmt->fetchAll();
$gerenciar=usuarioTemPermissao('CLIENTES_GERENCIAR');
renderHeader($cliente['nome_fantasia'], $cliente['razao_social'] . ' — ' . $cliente['cnpj']);
?>
<div class="toolbar">
    <a class="button secondary" href="/clientes/index.php">Voltar aos clientes</a>
    <span class="status <?= $cliente['ativo'] ? 'active' : 'inactive' ?>">Cliente <?= $cliente['ativo'] ? 'ativo' : 'inativo' ?></span>
    <?php if ($gerenciar): ?><a class="button secondary" href="/clientes/form.php?id=<?= $id ?>">Editar dados cadastrais</a><?php endif; ?>
</div>
<nav class="tabs" aria-label="Dados do cliente">
    <a href="/clientes/detalhes.php?id=<?= $id ?>&aba=enderecos" class="<?= $aba === 'enderecos' ? 'active' : '' ?>">Endereços</a>
    <a href="/clientes/detalhes.php?id=<?= $id ?>&aba=contatos" class="<?= $aba === 'contatos' ? 'active' : '' ?>">Contatos</a>
</nav>
<div class="toolbar">
    <form method="get" class="search-form">
        <input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aba" value="<?= e($aba) ?>">
        <select name="status" aria-label="Situação dos registros">
            <?php foreach (['ativos'=>'Ativos','inativos'=>'Inativos','todos'=>'Todos'] as $valor=>$rotulo): ?>
            <option value="<?= $valor ?>" <?= $status === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
            <?php endforeach; ?>
        </select><button class="button secondary">Filtrar</button>
    </form>
    <?php if ($gerenciar): ?>
    <a class="button primary" href="/clientes/relacionado-form.php?tipo=<?= $tipo ?>&cliente_id=<?= $id ?>">+ Novo <?= e($config['rotulo']) ?></a>
    <?php endif; ?>
</div>
<section class="panel table-panel"><div class="table-wrap"><table class="cliente-relacionados">
    <thead><tr><th><?= $tipo === 'endereco' ? 'Endereço' : 'Contato' ?></th><th><?= $tipo === 'endereco' ? 'Tipo / Localidade' : 'Departamento' ?></th><th>Telefones / E-mail</th><th>Status</th><th>Ações</th></tr></thead>
    <tbody>
    <?php if (!$registros): ?><tr><td colspan="5" class="empty">Nenhum <?= e($config['rotulo']) ?> encontrado.</td></tr><?php endif; ?>
    <?php foreach ($registros as $registro): ?>
        <tr>
            <td>
                <?php if ($tipo === 'endereco'): ?>
                    <strong><?= e($registro['logradouro'] . ($registro['numero'] ? ', ' . $registro['numero'] : '')) ?></strong>
                    <small><?= e($registro['complemento'] ?? '') ?></small><small>CEP <?= e($registro['cep']) ?></small>
                    <?php if ($registro['principal']): ?><small><?= $registro['ativo'] ? 'Principal' : 'Principal (inativo)' ?></small><?php endif; ?>
                    <small><?= e($registro['contato'] ?? '') ?></small>
                <?php else: ?><strong><?= e($registro['nome']) ?></strong><?php endif; ?>
                <?php if ($registro['observacao']): ?><small><?= e(mb_strimwidth($registro['observacao'],0,160,'…','UTF-8')) ?></small><?php endif; ?>
            </td>
            <td><?= e($registro['classificacao']) ?>
                <?php if ($tipo === 'endereco'): ?>
                <small><?= e($registro['bairro'] . ' — ' . $registro['cidade'] . '/' . $registro['uf']) ?></small>
                <?php if (!$registro['localidade_ativa']): ?><small>Localidade inativa</small><?php endif; ?>
                <?php endif; ?>
            </td>
            <td><?= e($registro['telefone'] ?? '—') ?><small><?= e($registro['celular'] ?? '') ?></small><small><?= e($registro['email'] ?? '') ?></small></td>
            <td><span class="status <?= $registro['ativo'] ? 'active' : 'inactive' ?>"><?= $registro['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
            <td class="actions">
                <?php if ($gerenciar): ?>
                <a href="/clientes/relacionado-form.php?tipo=<?= $tipo ?>&cliente_id=<?= $id ?>&id=<?= (int)$registro['id'] ?>">Editar</a>
                <form action="/clientes/alterar-status-relacionado.php" method="post" class="action-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="cliente_id" value="<?= $id ?>"><input type="hidden" name="tipo" value="<?= $tipo ?>">
                    <input type="hidden" name="id" value="<?= (int)$registro['id'] ?>"><input type="hidden" name="ativo" value="<?= $registro['ativo'] ? '0' : '1' ?>">
                    <button type="submit" class="action-link" onclick="return confirm('Deseja <?= $registro['ativo'] ? 'inativar' : 'reativar' ?> este <?= e($config['rotulo']) ?>?')"><?= $registro['ativo'] ? 'Inativar' : 'Reativar' ?></button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div></section>
<?php renderFooter(); ?>
