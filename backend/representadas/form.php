<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../config/database.php';

exigirPermissao('REPRESENTADAS_GERENCIAR');

$pdo = getDatabaseConnection();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$bairroSelecionado = isset($_GET['bairro_id']) ? (int) $_GET['bairro_id'] : 0;
$representada = [
    'id' => 0, 'nome' => '', 'cnpj' => '', 'inscricao_estadual' => '',
    'logradouro' => '', 'numero' => '', 'complemento' => '', 'bairro_id' => '',
    'cep' => '', 'telefone' => '', 'email' => '', 'ativo' => 1, 'observacao' => ''
];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM representadas WHERE id = :id AND empresa_id = :empresa_id');
    $stmt->execute(['id' => $id, 'empresa_id' => $_SESSION['empresa_id']]);
    $registro = $stmt->fetch();
    if (!$registro) {
        http_response_code(404);
        exit('Representada não encontrada.');
    }
    $representada = $registro;
}
if ($bairroSelecionado > 0) { $representada['bairro_id'] = $bairroSelecionado; }

$bairros = $pdo->query(
    'SELECT b.id, b.nome AS bairro, c.nome AS cidade, e.sigla
     FROM bairros b
     INNER JOIN cidades c ON c.id = b.cidade_id
     INNER JOIN estados e ON e.id = c.estado_id
     ORDER BY e.sigla, c.nome, b.nome'
)->fetchAll();

renderHeader($id ? 'Editar representada' : 'Nova representada', 'Informe os dados cadastrais e de contato.');
?>
<form action="/representadas/salvar.php" method="post" class="panel form-panel-admin">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="id" value="<?= (int) $representada['id'] ?>">

    <div class="form-grid">
        <label class="field span-2">Nome da representada
            <input name="nome" maxlength="200" required value="<?= e($representada['nome']) ?>">
        </label>
        <label class="field">CNPJ
            <input name="cnpj" maxlength="18" required value="<?= e($representada['cnpj']) ?>" data-mask="cnpj" data-validate-cnpj>
            <small class="field-feedback" data-cnpj-feedback>Informe um CNPJ válido.</small>
        </label>
        <label class="field">Inscrição estadual
            <input name="inscricao_estadual" maxlength="20" value="<?= e($representada['inscricao_estadual'] ?? '') ?>">
        </label>
        <label class="field span-2">Logradouro
            <input name="logradouro" maxlength="200" value="<?= e($representada['logradouro'] ?? '') ?>">
        </label>
        <label class="field">Número
            <input name="numero" maxlength="20" value="<?= e($representada['numero'] ?? '') ?>">
        </label>
        <label class="field">Complemento
            <input name="complemento" maxlength="200" value="<?= e($representada['complemento'] ?? '') ?>">
        </label>
        <label class="field span-2">Bairro / Cidade
            <select name="bairro_id">
                <option value="">Selecione</option>
                <?php foreach ($bairros as $bairro): ?>
                <option value="<?= (int) $bairro['id'] ?>" <?= (string) $bairro['id'] === (string) $representada['bairro_id'] ? 'selected' : '' ?>>
                    <?= e($bairro['bairro'] . ' — ' . $bairro['cidade'] . '/' . $bairro['sigla']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php if (usuarioTemPermissao('LOCALIDADES_GERENCIAR')): ?>
            <a class="inline-link" href="/localidades/bairro-form.php?retorno=representada<?= $id ? '&representada_id='.(int)$id : '' ?>">Bairro ou cidade não cadastrado? Cadastrar agora</a>
            <?php endif; ?>
        </label>
        <label class="field">CEP
            <input name="cep" maxlength="9" value="<?= e($representada['cep'] ?? '') ?>" data-mask="cep">
        </label>
        <label class="field">Telefone
            <input name="telefone" maxlength="20" value="<?= e($representada['telefone'] ?? '') ?>">
        </label>
        <label class="field span-2">E-mail
            <input type="email" name="email" maxlength="150" value="<?= e($representada['email'] ?? '') ?>">
        </label>
        <label class="field span-2">Observação
            <textarea name="observacao" rows="4"><?= e($representada['observacao'] ?? '') ?></textarea>
        </label>
        <label class="check-field">
            <input type="checkbox" name="ativo" value="1" <?= $representada['ativo'] ? 'checked' : '' ?>>
            Representada ativa
        </label>
    </div>

    <div class="form-actions">
        <a href="/representadas/index.php" class="button secondary">Cancelar</a>
        <button class="button primary">Salvar representada</button>
    </div>
</form>
<?php renderFooter(); ?>
