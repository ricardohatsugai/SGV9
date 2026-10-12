<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/relacionados.php';
exigirPermissao('CLIENTES_GERENCIAR');
$tipo=clienteTexto($_GET,'tipo');
$config=clienteRelacionadoConfig($tipo);
$clienteId=clienteId($_GET['cliente_id'] ?? null);
$id=array_key_exists('id',$_GET) ? clienteId($_GET['id']) : 0;
if (!$config || $clienteId === null || $id === null) { http_response_code(400); exit('Identificadores inválidos.'); }
$pdo=getDatabaseConnection();
$cliente=clienteConsultar($pdo,$clienteId,(int)$_SESSION['empresa_id']);
if (!$cliente) { http_response_code(404); exit('Cliente não encontrado.'); }
$registro=$id ? clienteRelacionadoConsultar($pdo,$config,$id,$clienteId) : array_fill_keys($config['campos'],'') + ['id'=>0,'ativo'=>1,'principal'=>0];
if (!$registro) { http_response_code(404); exit('Registro não encontrado neste cliente.'); }
$bairroAtual=$tipo === 'endereco' ? (int)$registro['bairro_id'] : 0;
$anteriores=$_SESSION['cliente_relacionado_form'] ?? null;
unset($_SESSION['cliente_relacionado_form']);
if ($anteriores && $anteriores['tipo'] === $tipo && $anteriores['cliente_id'] === $clienteId && $anteriores['id'] === $id) {
    $registro=array_replace($registro,$anteriores['dados']);
}
if ($tipo === 'endereco') {
    $opcoes=$pdo->query('SELECT id,tipo AS nome FROM tipos_endereco ORDER BY tipo')->fetchAll();
    $stmt=$pdo->prepare('SELECT b.id,b.nome AS bairro,c.nome AS cidade,e.sigla,
        (b.ativo=1 AND c.ativo=1 AND e.ativo=1) AS localidade_ativa FROM bairros b
        JOIN cidades c ON c.id=b.cidade_id JOIN estados e ON e.id=c.estado_id
        WHERE (b.ativo=1 AND c.ativo=1 AND e.ativo=1) OR b.id=:bairro_atual ORDER BY e.sigla,c.nome,b.nome');
    $stmt->execute(['bairro_atual'=>$bairroAtual]); $bairros=$stmt->fetchAll();
} else {
    $opcoes=$pdo->query('SELECT id,nome FROM departamentos_clientes ORDER BY nome')->fetchAll();
}
$campoClassificacao=$tipo === 'endereco' ? 'tipo_endereco_id' : 'departamento_id';
renderHeader(($id ? 'Editar ' : 'Novo ') . $config['rotulo'], $cliente['nome_fantasia']);
?>
<form action="/clientes/salvar-relacionado.php" method="post" class="panel form-panel-admin">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="tipo" value="<?= $tipo ?>"><input type="hidden" name="cliente_id" value="<?= $clienteId ?>">
    <input type="hidden" name="id" value="<?= (int)$registro['id'] ?>">
    <div class="form-grid">
        <label class="field"><?= $tipo === 'endereco' ? 'Tipo de endereço' : 'Departamento' ?>
            <select name="<?= $campoClassificacao ?>" required>
                <option value="">Selecione</option>
                <?php foreach ($opcoes as $opcao): ?><option value="<?= (int)$opcao['id'] ?>" <?= (string)$registro[$campoClassificacao] === (string)$opcao['id'] ? 'selected' : '' ?>><?= e($opcao['nome']) ?></option><?php endforeach; ?>
            </select>
        </label>
        <?php if ($tipo === 'endereco'): ?>
        <label class="field span-2">Logradouro<input name="logradouro" required maxlength="200" value="<?= e($registro['logradouro']) ?>"></label>
        <label class="field">Número<input name="numero" maxlength="20" value="<?= e($registro['numero'] ?? '') ?>"></label>
        <label class="field">Complemento<input name="complemento" maxlength="200" value="<?= e($registro['complemento'] ?? '') ?>"></label>
        <label class="field span-2">Bairro / Cidade
            <select name="bairro_id" required><option value="">Selecione</option>
                <?php foreach ($bairros as $bairro): ?><option value="<?= (int)$bairro['id'] ?>" <?= (string)$registro['bairro_id'] === (string)$bairro['id'] ? 'selected' : '' ?>><?= e($bairro['bairro'] . ' — ' . $bairro['cidade'] . '/' . $bairro['sigla'] . (!$bairro['localidade_ativa'] ? ' (localidade inativa)' : '')) ?></option><?php endforeach; ?>
            </select>
        </label>
        <label class="field">CEP<input name="cep" required maxlength="9" data-mask="cep" value="<?= e($registro['cep']) ?>"></label>
        <label class="field">Pessoa de contato<input name="contato" maxlength="100" value="<?= e($registro['contato'] ?? '') ?>"></label>
        <?php else: ?>
        <label class="field">Nome do contato<input name="nome" required maxlength="100" value="<?= e($registro['nome']) ?>"></label>
        <?php endif; ?>
        <label class="field">Telefone<input name="telefone" maxlength="20" value="<?= e($registro['telefone'] ?? '') ?>"></label>
        <label class="field">Celular<input name="celular" maxlength="20" value="<?= e($registro['celular'] ?? '') ?>"></label>
        <label class="field span-2">E-mail<input type="email" name="email" maxlength="150" value="<?= e($registro['email'] ?? '') ?>"></label>
        <label class="field span-2">Observação<textarea name="observacao" rows="4"><?= e($registro['observacao'] ?? '') ?></textarea></label>
        <?php if ($tipo === 'endereco'): ?>
        <label class="check-field"><input type="checkbox" name="principal" value="1" <?= $registro['principal'] ? 'checked' : '' ?>>Endereço principal</label>
        <p class="field span-2">Ao salvar um endereço ativo como principal, o principal anterior deixa de ser principal e continua cadastrado.</p>
        <?php endif; ?>
        <label class="check-field"><input type="checkbox" name="ativo" value="1" <?= $registro['ativo'] ? 'checked' : '' ?>>Registro ativo</label>
    </div>
    <div class="form-actions">
        <a class="button secondary" href="/clientes/detalhes.php?id=<?= $clienteId ?>&aba=<?= $config['aba'] ?>">Cancelar</a>
        <button class="button primary">Salvar <?= e($config['rotulo']) ?></button>
    </div>
</form>
<?php renderFooter(); ?>
