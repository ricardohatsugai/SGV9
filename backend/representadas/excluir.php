<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

exigirPermissao('REPRESENTADAS_GERENCIAR');

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    || !validateCsrfToken($_POST['csrf_token'] ?? null)
) {
    flash('error', 'Requisição inválida ou sessão expirada.');
    header('Location: /representadas/index.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'Representada inválida.');
    header('Location: /representadas/index.php');
    exit;
}

$pdo = getDatabaseConnection();

try {
    $pdo->beginTransaction();

    $consulta = $pdo->prepare(
        'SELECT *
         FROM representadas
         WHERE id = :id
           AND empresa_id = :empresa_id
         FOR UPDATE'
    );

    $consulta->execute([
        'id' => $id,
        'empresa_id' => $_SESSION['empresa_id'],
    ]);

    $representada = $consulta->fetch();

    if (!$representada) {
        throw new RuntimeException('Representada não encontrada.');
    }

    $vinculos = [
        'contatos_representadas' => 'Contatos',
        'produtos' => 'Produtos',
        'tabelas_precos' => 'Tabelas de preços',
        'politicas_comerciais' => 'Políticas comerciais',
        'promocoes' => 'Promoções',
        'formas_pagamento' => 'Formas de pagamento',
        'pedidos' => 'Pedidos',
        'faturamentos' => 'Faturamentos',
        'regras_comissoes' => 'Regras de comissões',
        'comissoes' => 'Comissões',
        'importacoes' => 'Importações',
    ];

    $vinculosEncontrados = [];

    foreach ($vinculos as $tabela => $descricao) {
        $sql = sprintf(
            'SELECT COUNT(*) FROM `%s` WHERE representada_id = :id',
            $tabela
        );

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        if ((int) $stmt->fetchColumn() > 0) {
            $vinculosEncontrados[] = $descricao;
        }
    }

    if ($vinculosEncontrados) {
        $pdo->rollBack();

        flash(
            'error',
            'A representada não pode ser excluída porque possui vínculos com: '
            . implode(', ', $vinculosEncontrados)
            . '. Utilize a opção Inativar.'
        );

        header('Location: /representadas/index.php?status=todas');
        exit;
    }

    $exclusao = $pdo->prepare(
        'DELETE FROM representadas
         WHERE id = :id
           AND empresa_id = :empresa_id'
    );

    $exclusao->execute([
        'id' => $id,
        'empresa_id' => $_SESSION['empresa_id'],
    ]);

    if ($exclusao->rowCount() !== 1) {
        throw new RuntimeException('A representada não foi excluída.');
    }

    $log = $pdo->prepare(
        'INSERT INTO logs_usuarios
        (
            empresa_id,
            usuario_id,
            acao,
            entidade,
            registro_id,
            dados_anteriores,
            endereco_ip,
            user_agent
        )
        VALUES
        (
            :empresa_id,
            :usuario_id,
            "REPRESENTADA_EXCLUIDA",
            "representadas",
            :registro_id,
            :dados_anteriores,
            :ip,
            :user_agent
        )'
    );

    $log->execute([
        'empresa_id' => $_SESSION['empresa_id'],
        'usuario_id' => $_SESSION['usuario_id'],
        'registro_id' => (string) $id,
        'dados_anteriores' => json_encode(
            $representada,
            JSON_UNESCAPED_UNICODE
        ),
        'ip' => substr(
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            0,
            45
        ),
        'user_agent' => substr(
            (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
            0,
            500
        ),
    ]);

    $pdo->commit();

    flash('success', 'Representada excluída definitivamente.');
} catch (PDOException $erro) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($erro->getMessage());

    if ($erro->getCode() === '23000') {
        flash(
            'error',
            'A representada possui registros relacionados e não pode ser excluída. Utilize a opção Inativar.'
        );
    } else {
        flash('error', 'Não foi possível excluir a representada.');
    }
} catch (Throwable $erro) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($erro->getMessage());

    flash('error', 'Não foi possível excluir a representada.');
}

header('Location: /representadas/index.php?status=todas');
exit;