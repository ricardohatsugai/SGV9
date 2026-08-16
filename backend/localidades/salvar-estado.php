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

$pdo = getDatabaseConnection();

$id = 0;

if (isset($_POST['id']) && $_POST['id'] !== '') {
    $idValidado = filter_var(
        $_POST['id'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($idValidado === false) {
        flash('error', 'Identificador de estado inválido.');
        header("Location: {$redirect}");
        exit;
    }

    $id = $idValidado;
}

$nome = mb_strtoupper(trim((string) ($_POST['nome'] ?? '')));
$sigla = mb_strtoupper(trim((string) ($_POST['sigla'] ?? '')));

if (
    $nome === ''
    || mb_strlen($nome) > 100
    || !preg_match('/^[A-Z]{2}$/', $sigla)
) {
    flash('error', 'Informe um nome válido e uma sigla com duas letras.');

    if ($id > 0) {
        $redirect .= '&editar_estado='.$id;
    }

    header("Location: {$redirect}");
    exit;
}

try {
    if ($id > 0) {
        $consulta = $pdo->prepare(
            'SELECT id
             FROM estados
             WHERE id = :id'
        );

        $consulta->execute(['id' => $id]);

        if (!$consulta->fetch()) {
            flash('error', 'Estado não encontrado.');
            header("Location: {$redirect}");
            exit;
        }
    }

    $duplicidade = $pdo->prepare(
        'SELECT id
         FROM estados
         WHERE (nome = :nome OR sigla = :sigla)
           AND id <> :id
         LIMIT 1'
    );

    $duplicidade->execute([
        'nome' => $nome,
        'sigla' => $sigla,
        'id' => $id,
    ]);

    if ($duplicidade->fetch()) {
        flash('error', 'Já existe um estado cadastrado com esse nome ou sigla.');

        if ($id > 0) {
            $redirect .= '&editar_estado='.$id;
        }

        header("Location: {$redirect}");
        exit;
    }

    if ($id > 0) {
        $salvar = $pdo->prepare(
            'UPDATE estados
             SET nome = :nome,
                 sigla = :sigla
             WHERE id = :id'
        );

        $salvar->execute([
            'nome' => $nome,
            'sigla' => $sigla,
            'id' => $id,
        ]);

        flash('success', 'Estado atualizado com sucesso.');
    } else {
        $salvar = $pdo->prepare(
            'INSERT INTO estados (nome, sigla)
             VALUES (:nome, :sigla)'
        );

        $salvar->execute([
            'nome' => $nome,
            'sigla' => $sigla,
        ]);

        flash('success', 'Estado cadastrado com sucesso.');
    }
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        flash('error', 'Já existe um estado cadastrado com esse nome ou sigla.');
    } else {
        flash('error', 'Não foi possível salvar o estado.');
    }

    if ($id > 0) {
        $redirect .= '&editar_estado='.$id;
    }
}

header("Location: {$redirect}");
exit;
