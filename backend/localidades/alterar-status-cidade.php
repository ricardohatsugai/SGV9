<?php

declare(strict_types=1);

require_once __DIR__.'/../auth.php';
require_once __DIR__.'/../includes/permissions.php';
require_once __DIR__.'/../includes/flash.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/session.php';

exigirPermissao('LOCALIDADES_GERENCIAR');

$redirect = '/localidades/index.php?aba=cidades';

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    || !validateCsrfToken($_POST['csrf_token'] ?? null)
) {
    flash('error', 'Requisição inválida.');
    header("Location: {$redirect}");
    exit;
}

$id = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$novoStatus = filter_var(
    $_POST['novo_status'] ?? null,
    FILTER_VALIDATE_INT
);

if (
    $id === false
    || $id === null
    || !in_array($novoStatus, [0, 1], true)
) {
    flash('error', 'Dados inválidos para alteração da situação.');
    header("Location: {$redirect}");
    exit;
}

$pdo = getDatabaseConnection();

try {
    $pdo->beginTransaction();

    $consultaCidade = $pdo->prepare(
        'SELECT
             c.id,
             c.nome,
             c.ativo,
             c.estado_id,
             e.ativo AS estado_ativo
         FROM cidades c
         JOIN estados e ON e.id = c.estado_id
         WHERE c.id = :id
         FOR UPDATE'
    );

    $consultaCidade->execute(['id' => $id]);
    $cidade = $consultaCidade->fetch();

    if (!$cidade) {
        $pdo->rollBack();

        flash('error', 'Cidade não encontrada.');
        header("Location: {$redirect}");
        exit;
    }

    if ((int) $cidade['ativo'] === $novoStatus) {
        $pdo->rollBack();

        flash(
            'info',
            $novoStatus === 1
                ? 'A cidade já está ativa.'
                : 'A cidade já está inativa.'
        );

        header("Location: {$redirect}");
        exit;
    }

    /*
     * Uma cidade somente pode ser reativada se o Estado
     * ao qual pertence também estiver ativo.
     */
    if (
        $novoStatus === 1
        && (int) $cidade['estado_ativo'] !== 1
    ) {
        $pdo->rollBack();

        flash(
            'error',
            'Não é possível reativar esta cidade porque o Estado '
            .'ao qual ela pertence está inativo.'
        );

        header("Location: {$redirect}");
        exit;
    }

    /*
     * Uma cidade não pode ser inativada enquanto possuir
     * bairros ativos vinculados.
     */
    if ($novoStatus === 0) {
        $consultaBairros = $pdo->prepare(
            'SELECT COUNT(*)
             FROM bairros
             WHERE cidade_id = :cidade_id
               AND ativo = 1'
        );

        $consultaBairros->execute(['cidade_id' => $id]);
        $quantidadeBairrosAtivos = (int) $consultaBairros->fetchColumn();

        if ($quantidadeBairrosAtivos > 0) {
            $pdo->rollBack();

            flash(
                'error',
                'Não é possível inativar esta cidade porque existem '
                .$quantidadeBairrosAtivos
                .' bairro(s) ativo(s) vinculado(s).'
            );

            header("Location: {$redirect}");
            exit;
        }
    }

    $alterarStatus = $pdo->prepare(
        'UPDATE cidades
         SET ativo = :ativo
         WHERE id = :id'
    );

    $alterarStatus->execute([
        'ativo' => $novoStatus,
        'id' => $id,
    ]);

    $pdo->commit();

    flash(
        'success',
        $novoStatus === 1
            ? 'Cidade reativada com sucesso.'
            : 'Cidade inativada com sucesso.'
    );
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash('error', 'Não foi possível alterar a situação da cidade.');
}

header("Location: {$redirect}");
exit;
