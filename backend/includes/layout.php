<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/flash.php';

function e(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function renderHeader(string $titulo, string $subtitulo = ''): void
{
    $flash = consumirFlash();
    $nome = e((string) $_SESSION['usuario_nome']);
    $perfil = e((string) $_SESSION['perfil_nome']);
    $empresa = e((string) $_SESSION['empresa_nome']);
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> | SGV9</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="sidebar-brand" href="/dashboard.php">
            <span class="brand-symbol">S9</span>
            <span><strong>SGV9</strong><small>Gestão de Vendas</small></span>
        </a>

        <nav class="nav">
            <a href="/dashboard.php" class="<?= $_SERVER['REQUEST_URI'] === '/dashboard.php' ? 'active' : '' ?>">
                <span>▦</span> Visão geral
            </a>

            <?php if (usuarioTemPermissao('REPRESENTADAS_VISUALIZAR')): ?>
            <a href="/representadas/index.php" class="<?= str_starts_with($_SERVER['REQUEST_URI'], '/representadas') ? 'active' : '' ?>">
                <span>◇</span> Representadas
            </a>
            <?php endif; ?>

            <?php if (usuarioTemPermissao('LOCALIDADES_VISUALIZAR')): ?>
            <a href="/localidades/index.php" class="<?= str_starts_with($_SERVER['REQUEST_URI'], '/localidades') ? 'active' : '' ?>">
                <span>⌖</span> Localidades
            </a>
            <?php endif; ?>

            <?php if (usuarioTemPermissao('CLIENTES_VISUALIZAR')): ?>
            <a href="#" class="disabled"><span>◎</span> Clientes <small>em breve</small></a>
            <?php endif; ?>

            <?php if (usuarioTemPermissao('PRODUTOS_VISUALIZAR')): ?>
            <a href="#" class="disabled"><span>□</span> Produtos <small>em breve</small></a>
            <?php endif; ?>

            <?php if (usuarioTemPermissao('PEDIDOS_VISUALIZAR')): ?>
            <a href="#" class="disabled"><span>≡</span> Pedidos <small>em breve</small></a>
            <?php endif; ?>

            <?php if (usuarioTemPermissao('COMISSOES_VISUALIZAR')): ?>
            <a href="#" class="disabled"><span>%</span> Comissões <small>em breve</small></a>
            <?php endif; ?>

            <?php if (usuarioTemPermissao('USUARIOS_GERENCIAR')): ?>
            <div class="nav-section">Administração</div>
            <a href="/usuarios/index.php" class="<?= str_starts_with($_SERVER['REQUEST_URI'], '/usuarios') ? 'active' : '' ?>">
                <span>♙</span> Usuários
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-user">
            <div class="avatar"><?= e(mb_strtoupper(mb_substr((string) $_SESSION['usuario_nome'], 0, 1))) ?></div>
            <div>
                <strong><?= $nome ?></strong>
                <small><?= $perfil ?></small>
            </div>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="menu-button" type="button" data-menu>☰</button>
            <div>
                <strong><?= $empresa ?></strong>
                <small>Ambiente administrativo</small>
            </div>
            <a href="/logout.php" class="logout-link">Sair</a>
        </header>

        <main class="content">
            <div class="page-heading">
                <div>
                    <p class="eyebrow">SGV9</p>
                    <h1><?= e($titulo) ?></h1>
                    <?php if ($subtitulo): ?><p><?= e($subtitulo) ?></p><?php endif; ?>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="flash <?= e($flash['tipo']) ?>"><?= e($flash['mensagem']) ?></div>
            <?php endif; ?>
    <?php
}

function renderFooter(): void
{
    ?>
        </main>
    </div>
</div>
<script src="/assets/js/admin.js" defer></script>
</body>
</html>
    <?php
}
