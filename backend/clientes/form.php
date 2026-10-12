<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/functions.php';

exigirPermissao('CLIENTES_GERENCIAR');

try {
    $id = idCliente(entradaCliente($_GET, 'id', '0'), true);
} catch (InvalidArgumentException $erro) {
    http_response_code(400);
    exit('Identificador de cliente inválido.');
}

try {
    $pdo = getDatabaseConnection();
    $estrutura = estruturaClientes($pdo);
    $cliente = [
        'id' => 0, 'data_cadastro' => (new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d'),
        'nome_fantasia' => '', 'razao_social' => '', 'cnpj' => '', 'inscricao_estadual' => '',
        'inscricao_municipal' => '', 'telefone' => '', 'email' => '', 'subcategoria_id' => '',
        'limite_credito' => '0.0000', 'observacao' => '', 'ativo' => 1, 'versao' => '',
    ];
    if ($id) {
        $registro = carregarCliente($pdo, $estrutura, $id, (int) $_SESSION['empresa_id']);
        if (!$registro) {
            http_response_code(404);
            exit('Cliente não encontrado.');
        }
        $cliente = $registro;
        $cliente['versao'] = versaoCliente($registro);
    }
    $anteriores = $_SESSION['cliente_form'] ?? null;
    unset($_SESSION['cliente_form']);
    if (is_array($anteriores) && ($anteriores['id'] ?? '0') === (string) $id) {
        $cliente = array_replace($cliente, $anteriores);
    }
    $subcategorias = $pdo->query(
        'SELECT s.id, s.nome, c.nome AS categoria FROM `' . $estrutura['subcategorias'] . '` s
         INNER JOIN `' . $estrutura['categorias'] . '` c ON c.id = s.categoria_id
         ORDER BY c.nome, s.nome'
    )->fetchAll();
} catch (Throwable $erro) {
    error_log('Erro ao abrir cadastro de cliente SGV9: ' . $erro->getMessage());
    flash('error', 'Não foi possível abrir o cadastro do cliente.');
    header('Location: /clientes/index.php');
    exit;
}

renderHeader($id ? 'Editar cliente' : 'Novo cliente', 'Dados cadastrais, classificação e contato do cliente.');
?>
<form action="/clientes/salvar.php" method="post" class="panel form-panel-admin">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="id" value="<?= (int) $cliente['id'] ?>">
    <input type="hidden" name="versao" value="<?= e($cliente['versao']) ?>">
    <div class="form-grid">
        <label class="field">Nome fantasia
            <input name="nome_fantasia" maxlength="200" required value="<?= e($cliente['nome_fantasia']) ?>">
        </label>
        <label class="field">Razão social
            <input name="razao_social" maxlength="200" required value="<?= e($cliente['razao_social']) ?>">
        </label>
        <label class="field">CNPJ
            <input name="cnpj" maxlength="18" required value="<?= e($cliente['cnpj']) ?>" data-mask="cnpj" data-validate-cnpj>
            <small class="field-feedback" data-cnpj-feedback>Informe um CNPJ válido.</small>
        </label>
        <label class="field">Data de cadastro
            <input type="date" name="data_cadastro" required value="<?= e($cliente['data_cadastro']) ?>">
        </label>
        <label class="field">Inscrição estadual
            <input name="inscricao_estadual" maxlength="20" value="<?= e($cliente['inscricao_estadual'] ?? '') ?>">
        </label>
        <label class="field">Inscrição municipal
            <input name="inscricao_municipal" maxlength="20" value="<?= e($cliente['inscricao_municipal'] ?? '') ?>">
        </label>
        <label class="field span-2">Categoria / Subcategoria
            <select name="subcategoria_id" required>
                <option value="">Selecione</option>
                <?php foreach ($subcategorias as $subcategoria): ?>
                    <option value="<?= (int) $subcategoria['id'] ?>" <?= (string) $subcategoria['id'] === (string) $cliente['subcategoria_id'] ? 'selected' : '' ?>>
                        <?= e($subcategoria['categoria'] . ' / ' . $subcategoria['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!$subcategorias): ?><small>Não há classificações disponíveis. Solicite a configuração ao administrador.</small><?php endif; ?>
        </label>
        <label class="field">Telefone
            <input name="telefone" maxlength="20" value="<?= e($cliente['telefone'] ?? '') ?>">
        </label>
        <label class="field">E-mail
            <input type="email" name="email" maxlength="<?= (int) $estrutura['email_max'] ?>" value="<?= e($cliente['email'] ?? '') ?>">
        </label>
        <label class="field">Limite de crédito (R$)
            <input name="limite_credito" inputmode="decimal" maxlength="20" required value="<?= e(str_replace('.', ',', (string) $cliente['limite_credito'])) ?>">
            <small>Exemplo: 1500,0000. Até quatro casas decimais, sem separador de milhar.</small>
        </label>
        <label class="check-field">
            <input type="checkbox" name="ativo" value="1" <?= (string) $cliente['ativo'] === '1' ? 'checked' : '' ?>>
            Cliente ativo
        </label>
        <label class="field span-2">Observação
            <textarea name="observacao" rows="4" maxlength="16383"><?= e($cliente['observacao'] ?? '') ?></textarea>
        </label>
    </div>
    <div class="form-actions">
        <a href="/clientes/index.php" class="button secondary">Cancelar</a>
        <button class="button primary" <?= !$subcategorias ? 'disabled' : '' ?>>Salvar cliente</button>
    </div>
</form>
<?php renderFooter(); ?>
