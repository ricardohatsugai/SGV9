<?php

declare(strict_types=1);

require_once __DIR__.'/../auth.php';
require_once __DIR__.'/../includes/permissions.php';
require_once __DIR__.'/../includes/flash.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/session.php';

exigirPermissao('LOCALIDADES_GERENCIAR');

$redirect = '/localidades/index.php?aba=estados';

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

    $consultaEstado = $pdo->prepare(
        'SELECT id, nome, ativo
         FROM estados
         WHERE id = :id
         FOR UPDATE'
    );

    $consultaEstado->execute(['id' => $id]);
    $estado = $consultaEstado->fetch();

    if (!$estado) {
        $pdo->rollBack();

        flash('error', 'Estado não encontrado.');
        header("Location: {$redirect}");
        exit;
    }

    if ((int) $estado['ativo'] === $novoStatus) {
        $pdo->rollBack();

        flash(
            'info',
            $novoStatus === 1
                ? 'O Estado já está ativo.'
                : 'O Estado já está inativo.'
        );

        header("Location: {$redirect}");
        exit;
    }

    /*
     * Um Estado não pode ser inativado enquanto possuir
     * cidades ativas vinculadas.
     */
    if ($novoStatus === 0) {
        $consultaCidades = $pdo->prepare(
            'SELECT COUNT(*)
             FROM cidades
             WHERE estado_id = :estado_id
               AND ativo = 1'
        );

        $consultaCidades->execute(['estado_id' => $id]);
        $quantidadeCidadesAtivas = (int) $consultaCidades->fetchColumn();

        if ($quantidadeCidadesAtivas > 0) {
            $pdo->rollBack();

            flash(
                'error',
                'Não é possível inativar este Estado porque existem '
                .$quantidadeCidadesAtivas
                .' cidade(s) ativa(s) vinculada(s).'
            );

            header("Location: {$redirect}");
            exit;
        }
    }

    $alterarStatus = $pdo->prepare(
        'UPDATE estados
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
            ? 'Estado reativado com sucesso.'
            : 'Estado inativado com sucesso.'
    );
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash('error', 'Não foi possível alterar a situação do Estado.');
}

header("Location: {$redirect}");
exit;
