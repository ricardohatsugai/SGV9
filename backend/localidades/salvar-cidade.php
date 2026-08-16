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

$id = 0;

if (isset($_POST['id']) && $_POST['id'] !== '') {
    $idValidado = filter_var(
        $_POST['id'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($idValidado === false) {
        flash('error', 'Identificador de cidade inválido.');
        header("Location: {$redirect}");
        exit;
    }

    $id = $idValidado;
}

$estadoId = filter_var(
    $_POST['estado_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$nome = mb_strtoupper(trim((string) ($_POST['nome'] ?? '')));

if (
    $nome === ''
    || mb_strlen($nome) > 150
    || $estadoId === false
    || $estadoId === null
) {
    flash('error', 'Informe um Estado e um nome válido para a cidade.');

    if ($id > 0) {
        $redirect .= '&editar_cidade='.$id;
    }

    header("Location: {$redirect}");
    exit;
}

$pdo = getDatabaseConnection();

try {
    /*
     * O Estado precisa existir e estar ativo para receber
     * uma nova cidade ou uma cidade transferida por edição.
     */
    $consultaEstado = $pdo->prepare(
        'SELECT id
         FROM estados
         WHERE id = :id
           AND ativo = 1'
    );

    $consultaEstado->execute(['id' => $estadoId]);

    if (!$consultaEstado->fetch()) {
        flash('error', 'O Estado selecionado não existe ou está inativo.');

        if ($id > 0) {
            $redirect .= '&editar_cidade='.$id;
        }

        header("Location: {$redirect}");
        exit;
    }

    if ($id > 0) {
        $consultaCidade = $pdo->prepare(
            'SELECT id
             FROM cidades
             WHERE id = :id'
        );

        $consultaCidade->execute(['id' => $id]);

        if (!$consultaCidade->fetch()) {
            flash('error', 'Cidade não encontrada.');
            header("Location: {$redirect}");
            exit;
        }
    }

    $duplicidade = $pdo->prepare(
        'SELECT id
         FROM cidades
         WHERE estado_id = :estado_id
           AND nome = :nome
           AND id <> :id
         LIMIT 1'
    );

    $duplicidade->execute([
        'estado_id' => $estadoId,
        'nome' => $nome,
        'id' => $id,
    ]);

    if ($duplicidade->fetch()) {
        flash('error', 'Essa cidade já está cadastrada no Estado selecionado.');

        if ($id > 0) {
            $redirect .= '&editar_cidade='.$id;
        }

        header("Location: {$redirect}");
        exit;
    }

    if ($id > 0) {
        $salvar = $pdo->prepare(
            'UPDATE cidades
             SET nome = :nome,
                 estado_id = :estado_id
             WHERE id = :id'
        );

        $salvar->execute([
            'nome' => $nome,
            'estado_id' => $estadoId,
            'id' => $id,
        ]);

        flash('success', 'Cidade atualizada com sucesso.');
    } else {
        $salvar = $pdo->prepare(
            'INSERT INTO cidades (nome, estado_id)
             VALUES (:nome, :estado_id)'
        );

        $salvar->execute([
            'nome' => $nome,
            'estado_id' => $estadoId,
        ]);

        flash('success', 'Cidade cadastrada com sucesso.');
    }
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        flash('error', 'Essa cidade já está cadastrada no Estado selecionado.');
    } else {
        flash('error', 'Não foi possível salvar a cidade.');
    }

    if ($id > 0) {
        $redirect .= '&editar_cidade='.$id;
    }
}

header("Location: {$redirect}");
exit;
