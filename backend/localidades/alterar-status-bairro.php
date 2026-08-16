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

    $consultaBairro = $pdo->prepare(
        'SELECT
             b.id,
             b.nome,
             b.ativo,
             b.cidade_id,
             c.ativo AS cidade_ativa,
             e.ativo AS estado_ativo
         FROM bairros b
         JOIN cidades c ON c.id = b.cidade_id
         JOIN estados e ON e.id = c.estado_id
         WHERE b.id = :id
         FOR UPDATE'
    );

    $consultaBairro->execute(['id' => $id]);
    $bairro = $consultaBairro->fetch();

    if (!$bairro) {
        $pdo->rollBack();

        flash('error', 'Bairro não encontrado.');
        header("Location: {$redirect}");
        exit;
    }

    if ((int) $bairro['ativo'] === $novoStatus) {
        $pdo->rollBack();

        flash(
            'info',
            $novoStatus === 1
                ? 'O bairro já está ativo.'
                : 'O bairro já está inativo.'
        );

        header("Location: {$redirect}");
        exit;
    }

    /*
     * Um bairro somente pode ser reativado quando a Cidade
     * e o Estado aos quais pertence estiverem ativos.
     */
    if ($novoStatus === 1) {
        if ((int) $bairro['estado_ativo'] !== 1) {
            $pdo->rollBack();

            flash(
                'error',
                'Não é possível reativar este bairro porque o Estado '
                .'ao qual ele pertence está inativo.'
            );

            header("Location: {$redirect}");
            exit;
        }

        if ((int) $bairro['cidade_ativa'] !== 1) {
            $pdo->rollBack();

            flash(
                'error',
                'Não é possível reativar este bairro porque a cidade '
                .'à qual ele pertence está inativa.'
            );

            header("Location: {$redirect}");
            exit;
        }
    }

    /*
     * Um bairro não pode ser inativado enquanto possuir registros
     * ativos vinculados em Representadas, Transportes ou Vendedores.
     */
    if ($novoStatus === 0) {
        $dependencias = [
            [
                'tabela' => 'representadas',
                'descricao' => 'representada(s)',
            ],
            [
                'tabela' => 'transportes',
                'descricao' => 'transporte(s)',
            ],
            [
                'tabela' => 'vendedores',
                'descricao' => 'vendedor(es)',
            ],
        ];

        foreach ($dependencias as $dependencia) {
            /*
             * Os nomes das tabelas são definidos internamente,
             * nunca recebidos da requisição.
             */
            $consultaDependencias = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM '.$dependencia['tabela'].'
                 WHERE bairro_id = :bairro_id
                   AND ativo = 1'
            );

            $consultaDependencias->execute(['bairro_id' => $id]);

            $quantidade = (int) $consultaDependencias->fetchColumn();

            if ($quantidade > 0) {
                $pdo->rollBack();

                flash(
                    'error',
                    'Não é possível inativar este bairro porque existem '
                    .$quantidade.' '.$dependencia['descricao']
                    .' ativo(s) vinculado(s).'
                );

                header("Location: {$redirect}");
                exit;
            }
        }
    }

    $alterarStatus = $pdo->prepare(
        'UPDATE bairros
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
            ? 'Bairro reativado com sucesso.'
            : 'Bairro inativado com sucesso.'
    );
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Erro ao alterar status do bairro: '
        .$exception->getMessage()
    );

    flash('error', 'Não foi possível alterar a situação do bairro.');
}

header("Location: {$redirect}");
exit;
