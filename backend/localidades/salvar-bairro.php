<?php

declare(strict_types=1);

require_once __DIR__.'/../auth.php';
require_once __DIR__.'/../includes/permissions.php';
require_once __DIR__.'/../includes/flash.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/session.php';

exigirPermissao('LOCALIDADES_GERENCIAR');

$redirect = '/localidades/index.php?aba=bairros';

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
        flash('error', 'Identificador de bairro inválido.');
        header("Location: {$redirect}");
        exit;
    }

    $id = $idValidado;
}

$cidadeId = filter_var(
    $_POST['cidade_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$representadaId = 0;

if (isset($_POST['representada_id']) && $_POST['representada_id'] !== '') {
    $representadaIdValidado = filter_var(
        $_POST['representada_id'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($representadaIdValidado === false) {
        flash('error', 'Identificador de representada inválido.');
        header("Location: {$redirect}");
        exit;
    }

    $representadaId = $representadaIdValidado;
}

$retorno = (string) ($_POST['retorno'] ?? '');
$retornoRepresentada = $retorno === 'representada' && $id === 0;

$nome = mb_strtoupper(trim((string) ($_POST['nome'] ?? '')));

/*
 * Em erros originados no formulário rápido, o usuário deve retornar
 * ao mesmo formulário, mantendo o contexto da Representada.
 */
if ($retornoRepresentada) {
    $redirect = '/localidades/bairro-form.php?retorno=representada';

    if ($representadaId > 0) {
        $redirect .= '&representada_id='.$representadaId;
    }
} elseif ($id > 0) {
    $redirect .= '&editar_bairro='.$id;
}

if (
    $nome === ''
    || mb_strlen($nome) > 200
    || $cidadeId === false
    || $cidadeId === null
) {
    flash('error', 'Informe uma cidade e um nome válido para o bairro.');
    header("Location: {$redirect}");
    exit;
}

$pdo = getDatabaseConnection();

try {
    /*
     * O bairro somente pode ser salvo em uma cidade ativa
     * pertencente a um Estado também ativo.
     */
    $consultaCidade = $pdo->prepare(
        'SELECT c.id
         FROM cidades c
         JOIN estados e ON e.id = c.estado_id
         WHERE c.id = :id
           AND c.ativo = 1
           AND e.ativo = 1'
    );

    $consultaCidade->execute(['id' => $cidadeId]);

    if (!$consultaCidade->fetch()) {
        flash(
            'error',
            'A cidade selecionada não existe, está inativa '
            .'ou pertence a um Estado inativo.'
        );

        header("Location: {$redirect}");
        exit;
    }

    if ($id > 0) {
        $consultaBairro = $pdo->prepare(
            'SELECT id
             FROM bairros
             WHERE id = :id'
        );

        $consultaBairro->execute(['id' => $id]);

        if (!$consultaBairro->fetch()) {
            flash('error', 'Bairro não encontrado.');
            header('Location: /localidades/index.php?aba=bairros');
            exit;
        }
    }

    $duplicidade = $pdo->prepare(
        'SELECT id
         FROM bairros
         WHERE cidade_id = :cidade_id
           AND nome = :nome
           AND id <> :id
         LIMIT 1'
    );

    $duplicidade->execute([
        'cidade_id' => $cidadeId,
        'nome' => $nome,
        'id' => $id,
    ]);

    if ($duplicidade->fetch()) {
        flash('error', 'Esse bairro já está cadastrado na cidade selecionada.');
        header("Location: {$redirect}");
        exit;
    }

    if ($id > 0) {
        $salvar = $pdo->prepare(
            'UPDATE bairros
             SET nome = :nome,
                 cidade_id = :cidade_id
             WHERE id = :id'
        );

        $salvar->execute([
            'nome' => $nome,
            'cidade_id' => $cidadeId,
            'id' => $id,
        ]);

        flash('success', 'Bairro atualizado com sucesso.');
    } else {
        $salvar = $pdo->prepare(
            'INSERT INTO bairros (nome, cidade_id)
             VALUES (:nome, :cidade_id)'
        );

        $salvar->execute([
            'nome' => $nome,
            'cidade_id' => $cidadeId,
        ]);

        $bairroId = (int) $pdo->lastInsertId();

        flash('success', 'Bairro cadastrado com sucesso.');

        if ($retornoRepresentada) {
            $url = '/representadas/form.php?bairro_id='.$bairroId;

            if ($representadaId > 0) {
                $url .= '&id='.$representadaId;
            }

            header("Location: {$url}");
            exit;
        }
    }
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        flash('error', 'Esse bairro já está cadastrado na cidade selecionada.');
    } else {
        flash('error', 'Não foi possível salvar o bairro.');
    }
}

header("Location: {$redirect}");
exit;
