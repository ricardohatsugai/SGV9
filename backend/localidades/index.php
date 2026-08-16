<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/layout.php';
require_once __DIR__.'/../config/database.php';
exigirPermissao('LOCALIDADES_VISUALIZAR');
$pdo = getDatabaseConnection();
$aba = $_GET['aba'] ?? 'bairros';

$estados = $pdo->query(
    'SELECT id, nome, sigla, ativo
     FROM estados
     ORDER BY nome'
)->fetchAll();

$cidades = $pdo->query(
    'SELECT
         c.id,
         c.nome,
         c.estado_id,
         c.ativo,
         e.sigla,
         e.nome AS estado,
         e.ativo AS estado_ativo
     FROM cidades c
     JOIN estados e ON e.id = c.estado_id
     ORDER BY e.sigla, c.nome'
)->fetchAll();

$bairros = $pdo->query(
    'SELECT
         b.id,
         b.nome,
         b.cidade_id,
         b.ativo,
         c.nome AS cidade,
         c.ativo AS cidade_ativa,
         e.sigla,
         e.ativo AS estado_ativo
     FROM bairros b
     JOIN cidades c ON c.id = b.cidade_id
     JOIN estados e ON e.id = c.estado_id
     ORDER BY e.sigla, c.nome, b.nome'
)->fetchAll();

$bairroEmEdicao = null;

$editarBairroId = filter_var(
    $_GET['editar_bairro'] ?? 0,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($aba === 'bairros' && $editarBairroId !== false) {
    $consultaBairro = $pdo->prepare(
        'SELECT id, nome, cidade_id
         FROM bairros
         WHERE id = :id'
    );

    $consultaBairro->execute(['id' => $editarBairroId]);
    $bairroEmEdicao = $consultaBairro->fetch() ?: null;

    if ($bairroEmEdicao === null) {
        flash('error', 'Bairro não encontrado.');
        header('Location: /localidades/index.php?aba=bairros');
        exit;
    }
}

$cidadeEmEdicao = null;

$editarCidadeId = filter_var(
    $_GET['editar_cidade'] ?? 0,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($aba === 'cidades' && $editarCidadeId !== false) {
    $consultaCidade = $pdo->prepare(
        'SELECT id, nome, estado_id
         FROM cidades
         WHERE id = :id'
    );

    $consultaCidade->execute(['id' => $editarCidadeId]);
    $cidadeEmEdicao = $consultaCidade->fetch() ?: null;

    if ($cidadeEmEdicao === null) {
        flash('error', 'Cidade não encontrada.');
        header('Location: /localidades/index.php?aba=cidades');
        exit;
    }
}

$estadoEmEdicao = null;

$editarEstadoId = filter_var(
    $_GET['editar_estado'] ?? 0,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($aba === 'estados' && $editarEstadoId !== false) {
    $consultaEstado = $pdo->prepare(
        'SELECT id, nome, sigla
         FROM estados
         WHERE id = :id'
    );

    $consultaEstado->execute(['id' => $editarEstadoId]);
    $estadoEmEdicao = $consultaEstado->fetch() ?: null;

    if ($estadoEmEdicao === null) {
        flash('error', 'Estado não encontrado.');
        header('Location: /localidades/index.php?aba=estados');
        exit;
    }
}
renderHeader('Localidades','Cadastre estados, cidades e bairros usados no sistema.');
?>
<div class="tabs">
<a href="?aba=bairros" class="<?= $aba==='bairros'?'active':'' ?>">Bairros</a>
<a href="?aba=cidades" class="<?= $aba==='cidades'?'active':'' ?>">Cidades</a>
<a href="?aba=estados" class="<?= $aba==='estados'?'active':'' ?>">Estados</a>
</div>
<?php if ($aba === 'estados'): ?>
    <div class="split-grid">
        <?php if (usuarioTemPermissao('LOCALIDADES_GERENCIAR')): ?>
            <form
                action="/localidades/salvar-estado.php"
                method="post"
                class="panel compact-form"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrfToken()) ?>"
                >

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) ($estadoEmEdicao['id'] ?? 0) ?>"
                >

                <h2>
                    <?= $estadoEmEdicao !== null
                        ? 'Editar estado'
                        : 'Novo estado' ?>
                </h2>

                <div class="form-grid">
                    <label class="field span-2">
                        Nome

                        <input
                            name="nome"
                            required
                            maxlength="100"
                            value="<?= e($estadoEmEdicao['nome'] ?? '') ?>"
                        >
                    </label>

                    <label class="field">
                        Sigla

                        <input
                            name="sigla"
                            required
                            maxlength="2"
                            value="<?= e($estadoEmEdicao['sigla'] ?? '') ?>"
                        >
                    </label>
                </div>

                <div class="form-actions">
                    <button class="button primary" type="submit">
                        <?= $estadoEmEdicao !== null
                            ? 'Salvar alterações'
                            : 'Salvar estado' ?>
                    </button>

                    <?php if ($estadoEmEdicao !== null): ?>
                        <a
                            href="/localidades/index.php?aba=estados"
                            class="button secondary"
                        >
                            Cancelar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>

        <section class="panel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Estado</th>
                            <th>Sigla</th>
                            <th>Situação</th>

                            <?php if (usuarioTemPermissao('LOCALIDADES_GERENCIAR')): ?>
                                <th>Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($estados as $estado): ?>
                            <tr>
                                <td><?= e($estado['nome']) ?></td>
                                <td><?= e($estado['sigla']) ?></td>

                                <td>
                                    <?= (int) $estado['ativo'] === 1
                                        ? 'Ativo'
                                        : 'Inativo' ?>
                                </td>

                                <?php if (usuarioTemPermissao('LOCALIDADES_GERENCIAR')): ?>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <a
                                                href="/localidades/index.php?aba=estados&editar_estado=<?= (int) $estado['id'] ?>"
                                                class="button secondary"
                                            >
                                                Editar
                                            </a>

                                            <form
                                                action="/localidades/alterar-status-estado.php"
                                                method="post"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e(csrfToken()) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $estado['id'] ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="novo_status"
                                                    value="<?= (int) $estado['ativo'] === 1 ? 0 : 1 ?>"
                                                >

                                                <?php if ((int) $estado['ativo'] === 1): ?>
                                                    <button
                                                        type="submit"
                                                        class="button danger"
                                                        onclick="return confirm('Deseja realmente inativar este Estado?');"
                                                    >
                                                        Inativar
                                                    </button>
                                                <?php else: ?>
                                                    <button
                                                        type="submit"
                                                        class="button secondary"
                                                    >
                                                        Reativar
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
<?php elseif ($aba === 'cidades'): ?>
    <div class="split-grid">
        <?php if (usuarioTemPermissao('LOCALIDADES_GERENCIAR')): ?>
            <form
                action="/localidades/salvar-cidade.php"
                method="post"
                class="panel compact-form"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrfToken()) ?>"
                >

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) ($cidadeEmEdicao['id'] ?? 0) ?>"
                >

                <h2>
                    <?= $cidadeEmEdicao !== null
                        ? 'Editar cidade'
                        : 'Nova cidade' ?>
                </h2>

                <div class="form-grid">
                    <label class="field span-2">
                        Estado

                        <select name="estado_id" required>
                            <option value="">Selecione</option>

                            <?php foreach ($estados as $estado): ?>
                                <?php
                                $estadoSelecionado =
                                    (int) ($cidadeEmEdicao['estado_id'] ?? 0)
                                    === (int) $estado['id'];

                                $estadoDisponivel =
                                    (int) $estado['ativo'] === 1
                                    || $estadoSelecionado;
                                ?>

                                <?php if ($estadoDisponivel): ?>
                                    <option
                                        value="<?= (int) $estado['id'] ?>"
                                        <?= $estadoSelecionado ? 'selected' : '' ?>
                                    >
                                        <?= e($estado['nome'].'/'.$estado['sigla']) ?>
                                        <?= (int) $estado['ativo'] === 0
                                            ? ' — Inativo'
                                            : '' ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="field span-2">
                        Cidade

                        <input
                            name="nome"
                            required
                            maxlength="150"
                            value="<?= e($cidadeEmEdicao['nome'] ?? '') ?>"
                        >
                    </label>
                </div>

                <div class="form-actions">
                    <button class="button primary" type="submit">
                        <?= $cidadeEmEdicao !== null
                            ? 'Salvar alterações'
                            : 'Salvar cidade' ?>
                    </button>

                    <?php if ($cidadeEmEdicao !== null): ?>
                        <a
                            href="/localidades/index.php?aba=cidades"
                            class="button secondary"
                        >
                            Cancelar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>

        <section class="panel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Cidade</th>
                            <th>Estado</th>
                            <th>UF</th>
                            <th>Situação</th>

                            <?php if (usuarioTemPermissao('LOCALIDADES_GERENCIAR')): ?>
                                <th>Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($cidades as $cidade): ?>
                            <tr>
                                <td><?= e($cidade['nome']) ?></td>
                                <td><?= e($cidade['estado']) ?></td>
                                <td><?= e($cidade['sigla']) ?></td>

                                <td>
                                    <?= (int) $cidade['ativo'] === 1
                                        ? 'Ativa'
                                        : 'Inativa' ?>
                                </td>

                                <?php if (usuarioTemPermissao('LOCALIDADES_GERENCIAR')): ?>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <a
                                                href="/localidades/index.php?aba=cidades&editar_cidade=<?= (int) $cidade['id'] ?>"
                                                class="button secondary"
                                            >
                                                Editar
                                            </a>

                                            <form
                                                action="/localidades/alterar-status-cidade.php"
                                                method="post"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e(csrfToken()) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $cidade['id'] ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="novo_status"
                                                    value="<?= (int) $cidade['ativo'] === 1 ? 0 : 1 ?>"
                                                >

                                                <?php if ((int) $cidade['ativo'] === 1): ?>
                                                    <button
                                                        type="submit"
                                                        class="button danger"
                                                        onclick="return confirm('Deseja realmente inativar esta cidade?');"
                                                    >
                                                        Inativar
                                                    </button>
                                                <?php else: ?>
                                                    <button
                                                        type="submit"
                                                        class="button secondary"
                                                    >
                                                        Reativar
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
<?php else: ?>
    <div class="split-grid">
        <?php if (usuarioTemPermissao('LOCALIDADES_GERENCIAR')): ?>
            <form
                action="/localidades/salvar-bairro.php"
                method="post"
                class="panel compact-form"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrfToken()) ?>"
                >

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) ($bairroEmEdicao['id'] ?? 0) ?>"
                >

                <h2>
                    <?= $bairroEmEdicao !== null
                        ? 'Editar bairro'
                        : 'Novo bairro' ?>
                </h2>

                <div class="form-grid">
                    <label class="field span-2">
                        Cidade

                        <select name="cidade_id" required>
                            <option value="">Selecione</option>

                            <?php foreach ($cidades as $cidade): ?>
                                <?php
                                $cidadeSelecionada =
                                    (int) ($bairroEmEdicao['cidade_id'] ?? 0)
                                    === (int) $cidade['id'];

                                $cidadeDisponivel =
                                    (
                                        (int) $cidade['ativo'] === 1
                                        && (int) $cidade['estado_ativo'] === 1
                                    )
                                    || $cidadeSelecionada;
                                ?>

                                <?php if ($cidadeDisponivel): ?>
                                    <option
                                        value="<?= (int) $cidade['id'] ?>"
                                        <?= $cidadeSelecionada ? 'selected' : '' ?>
                                    >
                                        <?= e($cidade['nome'].'/'.$cidade['sigla']) ?>

                                        <?php if (
                                            (int) $cidade['ativo'] !== 1
                                            || (int) $cidade['estado_ativo'] !== 1
                                        ): ?>
                                            — Indisponível
                                        <?php endif; ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="field span-2">
                        Bairro

                        <input
                            name="nome"
                            required
                            maxlength="200"
                            value="<?= e($bairroEmEdicao['nome'] ?? '') ?>"
                        >
                    </label>
                </div>

                <div class="form-actions">
                    <button class="button primary" type="submit">
                        <?= $bairroEmEdicao !== null
                            ? 'Salvar alterações'
                            : 'Salvar bairro' ?>
                    </button>

                    <?php if ($bairroEmEdicao !== null): ?>
                        <a
                            href="/localidades/index.php?aba=bairros"
                            class="button secondary"
                        >
                            Cancelar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>

        <section class="panel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Bairro</th>
                            <th>Cidade</th>
                            <th>UF</th>
                            <th>Situação</th>

                            <?php if (usuarioTemPermissao('LOCALIDADES_GERENCIAR')): ?>
                                <th>Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($bairros as $bairro): ?>
                            <tr>
                                <td><?= e($bairro['nome']) ?></td>
                                <td><?= e($bairro['cidade']) ?></td>
                                <td><?= e($bairro['sigla']) ?></td>

                                <td>
                                    <?= (int) $bairro['ativo'] === 1
                                        ? 'Ativo'
                                        : 'Inativo'
                                    ?>
                                </td>

                                <?php if (usuarioTemPermissao('LOCALIDADES_GERENCIAR')): ?>
                                    <td>
                                        <div class="table-actions">
                                            <a
                                                href="/localidades/index.php?aba=bairros&editar_bairro=<?= (int) $bairro['id'] ?>"
                                                class="button secondary"
                                            >
                                                Editar
                                            </a>

                                            <form
                                                action="/localidades/alterar-status-bairro.php"
                                                method="post"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e(csrfToken()) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $bairro['id'] ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="novo_status"
                                                    value="<?= (int) $bairro['ativo'] === 1 ? 0 : 1 ?>"
                                                >

                                                <?php if ((int) $bairro['ativo'] === 1): ?>
                                                    <button
                                                        type="submit"
                                                        class="button danger"
                                                        onclick="return confirm('Deseja realmente inativar este bairro?');"
                                                    >
                                                        Inativar
                                                    </button>
                                                <?php else: ?>
                                                    <button
                                                        type="submit"
                                                        class="button secondary"
                                                    >
                                                        Reativar
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
</table>
            </div>
        </section>
    </div>
<?php endif; renderFooter(); ?>
