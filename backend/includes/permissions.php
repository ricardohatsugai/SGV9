<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function carregarPermissoesUsuario(int $usuarioId): array
{
    $pdo = getDatabaseConnection();

    $stmt = $pdo->prepare(
        'SELECT pe.codigo
         FROM usuarios u
         INNER JOIN perfil_permissoes pp ON pp.perfil_id = u.perfil_id
         INNER JOIN permissoes pe ON pe.id = pp.permissao_id
         WHERE u.id = :usuario_id'
    );
    $stmt->execute(['usuario_id' => $usuarioId]);

    return array_column($stmt->fetchAll(), 'codigo');
}

function permissoesUsuario(): array
{
    if (!isset($_SESSION['permissoes'])) {
        $_SESSION['permissoes'] = carregarPermissoesUsuario((int) $_SESSION['usuario_id']);
    }

    return $_SESSION['permissoes'];
}

function usuarioTemPermissao(string $codigo): bool
{
    return in_array($codigo, permissoesUsuario(), true);
}

function exigirPermissao(string $codigo): void
{
    if (!usuarioTemPermissao($codigo)) {
        http_response_code(403);
        require __DIR__ . '/../403.php';
        exit;
    }
}
