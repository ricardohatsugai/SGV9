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
$ativo = (int) ($_POST['ativo'] ?? -1);

if ($id <= 0 || !in_array($ativo, [0, 1], true)) {
    flash('error', 'Dados inválidos para alteração de status.');
    header('Location: /representadas/index.php');
    exit;
}

$pdo = getDatabaseConnection();

try {
    $pdo->beginTransaction();

    $consulta = $pdo->prepare(
        'SELECT id, nome, ativo
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

    $atualizacao = $pdo->prepare(
        'UPDATE representadas
         SET ativo = :ativo
         WHERE id = :id
           AND empresa_id = :empresa_id'
    );

    $atualizacao->execute([
        'ativo' => $ativo,
        'id' => $id,
        'empresa_id' => $_SESSION['empresa_id'],
    ]);

    $log = $pdo->prepare(
        'INSERT INTO logs_usuarios
        (
            empresa_id,
            usuario_id,
            acao,
            entidade,
            registro_id,
            dados_anteriores,
            dados_novos,
            endereco_ip,
            user_agent
        )
        VALUES
        (
            :empresa_id,
            :usuario_id,
            :acao,
            "representadas",
            :registro_id,
            :dados_anteriores,
            :dados_novos,
            :ip,
            :user_agent
        )'
    );

    $log->execute([
        'empresa_id' => $_SESSION['empresa_id'],
        'usuario_id' => $_SESSION['usuario_id'],
        'acao' => $ativo
            ? 'REPRESENTADA_REATIVADA'
            : 'REPRESENTADA_INATIVADA',
        'registro_id' => (string) $id,
        'dados_anteriores' => json_encode(
            ['ativo' => (int) $representada['ativo']],
            JSON_UNESCAPED_UNICODE
        ),
        'dados_novos' => json_encode(
            ['ativo' => $ativo],
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

    flash(
        'success',
        $ativo
            ? 'Representada reativada com sucesso.'
            : 'Representada inativada com sucesso.'
    );
} catch (Throwable $erro) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($erro->getMessage());

    flash('error', 'Não foi possível alterar o status da representada.');
}

header('Location: /representadas/index.php?status=todas');
exit;