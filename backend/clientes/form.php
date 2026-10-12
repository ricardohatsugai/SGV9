<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/functions.php';
exigirPermissao('CLIENTES_GERENCIAR');

$pdo = getDatabaseConnection();
$id = array_key_exists('id', $_GET) ? clienteId($_GET['id']) : 0;
if ($id === null) {
    http_response_code(400);
    exit('Identificador de cliente inválido.');
}
$cliente = $id ? clienteConsultar($pdo, $id, (int) $_SESSION['empresa_id']) : clientePadrao();
if (!$cliente) {
    http_response_code(404);
    exit('Cliente não encontrado.');
}
$anteriores = $_SESSION['cliente_form'] ?? null;
unset($_SESSION['cliente_form']);
if ($anteriores && $anteriores['id'] === $id) {
    $cliente = array_replace($cliente, $anteriores['dados']);
}
$subcategorias = $pdo->query('SELECT s.id, s.nome, c.nome AS categoria FROM subcategorias_clientes s
    INNER JOIN categorias_clientes c ON c.id=s.categoria_id ORDER BY c.nome, s.nome')->fetchAll();

renderHeader($id ? 'Editar cliente' : 'Novo cliente', 'Informe os dados cadastrais e de contato.');
?>
<?php if ($id): ?>
<div class="toolbar"><a class="button secondary" href="/clientes/detalhes.php?id=<?= $id ?>">Gerenciar endereços e contatos</a></div>
<?php else: ?>
<p>Salve o cliente para cadastrar seus endereços e contatos.</p>
<?php endif; ?>
<?php if (!$subcategorias): ?>
    <div class="flash error">Não existem subcategorias de clientes cadastradas. Solicite a configuração ao administrador antes de salvar.</div>
<?php endif; ?>
<form action="/clientes/salvar.php" method="post" class="panel form-panel-admin">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="id" value="<?= (int) $cliente['id'] ?>">
    <div class="form-grid">
        <label class="field span-2">Nome fantasia
            <input name="nome_fantasia" maxlength="200" required value="<?= e($cliente['nome_fantasia']) ?>">
        </label>
        <label class="field span-2">Razão social
            <input name="razao_social" maxlength="200" required value="<?= e($cliente['razao_social']) ?>">
        </label>
        <label class="field">CNPJ
            <input name="cnpj" maxlength="18" required value="<?= e($cliente['cnpj']) ?>" data-mask="cnpj" data-validate-cnpj>
            <small class="field-feedback" data-cnpj-feedback>Informe um CNPJ válido.</small>
        </label>
        <label class="field">Data de cadastro
            <input type="date" name="data_cadastro" min="1000-01-01" max="9999-12-31" required value="<?= e($cliente['data_cadastro']) ?>">
        </label>
        <label class="field span-2">Categoria / Subcategoria
            <select name="subcategoria_id" required>
                <option value="">Selecione</option>
                <?php foreach ($subcategorias as $subcategoria): ?>
                    <option value="<?= (int) $subcategoria['id'] ?>" <?= (string) $cliente['subcategoria_id'] === (string) $subcategoria['id'] ? 'selected' : '' ?>>
                        <?= e($subcategoria['categoria'] . ' — ' . $subcategoria['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">Inscrição estadual
            <input name="inscricao_estadual" maxlength="20" value="<?= e($cliente['inscricao_estadual'] ?? '') ?>">
        </label>
        <label class="field">Inscrição municipal
            <input name="inscricao_municipal" maxlength="20" value="<?= e($cliente['inscricao_municipal'] ?? '') ?>">
        </label>
        <label class="field">Telefone
            <input name="telefone" maxlength="20" value="<?= e($cliente['telefone'] ?? '') ?>">
        </label>
        <label class="field">E-mail
            <input type="email" name="email" maxlength="150" value="<?= e($cliente['email'] ?? '') ?>">
        </label>
        <label class="field">Limite de crédito (R$)
            <input name="limite_credito" inputmode="decimal" maxlength="20" required value="<?= e((string) $cliente['limite_credito']) ?>">
            <small>Use vírgula ou ponto para decimais, sem separador de milhares.</small>
        </label>
        <label class="field">Situação cadastral
            <input name="situacao_cadastral" maxlength="50" value="<?= e($cliente['situacao_cadastral'] ?? '') ?>">
        </label>
        <label class="field span-2">Observação
            <textarea name="observacao" rows="4"><?= e($cliente['observacao'] ?? '') ?></textarea>
        </label>
        <label class="check-field">
            <input type="checkbox" name="ativo" value="1" <?= $cliente['ativo'] ? 'checked' : '' ?>> Cliente ativo
        </label>
    </div>
    <div class="form-actions">
        <a href="/clientes/index.php" class="button secondary">Cancelar</a>
        <button class="button primary" <?= !$subcategorias ? 'disabled' : '' ?>>Salvar cliente</button>
    </div>
</form>
<?php renderFooter(); ?>
