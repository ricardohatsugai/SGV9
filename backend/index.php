<?php
declare(strict_types=1);

require_once __DIR__ . '/config/session.php';

startSecureSession();

if (!empty($_SESSION['usuario_id'])) {
    header('Location: /dashboard.php');
    exit;
}

$error = $_SESSION['login_error'] ?? null;
$oldEmail = $_SESSION['old_email'] ?? '';

unset($_SESSION['login_error'], $_SESSION['old_email']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Acesso ao Sistema de Gestão de Vendas SGV9">
    <title>Login | SGV9</title>
    <link rel="stylesheet" href="/assets/css/login.css">
</head>
<body>
    <main class="login-page">
        <section class="brand-panel" aria-label="Apresentação do SGV9">
            <div class="brand-content">
                <div class="brand-mark" aria-hidden="true">S9</div>
                <p class="eyebrow">Gestão comercial inteligente</p>
                <h1>SGV9</h1>
                <p class="brand-description">
                    Pedidos, representadas, clientes e comissões em um único ambiente.
                </p>

                <div class="feature-list">
                    <div class="feature">
                        <span class="feature-icon" aria-hidden="true">✓</span>
                        <span>Acompanhamento completo do ciclo de vendas</span>
                    </div>
                    <div class="feature">
                        <span class="feature-icon" aria-hidden="true">✓</span>
                        <span>Políticas comerciais e tabelas de preços</span>
                    </div>
                    <div class="feature">
                        <span class="feature-icon" aria-hidden="true">✓</span>
                        <span>Controle de faturamento e comissões</span>
                    </div>
                </div>
            </div>

            <p class="brand-footer">Sistema de Gestão de Vendas · Versão 9</p>
        </section>

        <section class="form-panel">
            <div class="login-card">
                <div class="mobile-brand">
                    <div class="brand-mark small" aria-hidden="true">S9</div>
                    <strong>SGV9</strong>
                </div>

                <header class="login-header">
                    <p class="eyebrow dark">Área restrita</p>
                    <h2>Bem-vindo de volta</h2>
                    <p>Entre com suas credenciais para acessar o sistema.</p>
                </header>

                <?php if ($error): ?>
                    <div class="alert" role="alert">
                        <span aria-hidden="true">!</span>
                        <p><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                <?php endif; ?>

                <form action="/login.php" method="post" class="login-form" novalidate>
                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">

                    <div class="field">
                        <label for="email">E-mail</label>
                        <div class="input-wrapper">
                            <span class="input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="20" height="20">
                                    <path d="M3 5h18v14H3zM3 7l9 6 9-6"
                                          fill="none" stroke="currentColor"
                                          stroke-width="1.8" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                autocomplete="username"
                                maxlength="200"
                                placeholder="seuemail@empresa.com.br"
                                value="<?= htmlspecialchars((string) $oldEmail, ENT_QUOTES, 'UTF-8') ?>"
                                required
                                autofocus
                            >
                        </div>
                    </div>

                    <div class="field">
                        <div class="label-row">
                            <label for="senha">Senha</label>
                            <a href="#" class="helper-link" aria-disabled="true"
                               title="Recuperação de senha será implementada posteriormente">
                                Esqueci minha senha
                            </a>
                        </div>

                        <div class="input-wrapper">
                            <span class="input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="20" height="20">
                                    <rect x="5" y="10" width="14" height="10" rx="2"
                                          fill="none" stroke="currentColor" stroke-width="1.8"/>
                                    <path d="M8 10V7a4 4 0 018 0v3"
                                          fill="none" stroke="currentColor" stroke-width="1.8"/>
                                </svg>
                            </span>
                            <input
                                id="senha"
                                name="senha"
                                type="password"
                                autocomplete="current-password"
                                placeholder="Digite sua senha"
                                required
                            >
                            <button type="button" class="password-toggle"
                                    data-password-toggle aria-label="Mostrar senha">
                                <svg class="eye-open" viewBox="0 0 24 24" width="20" height="20">
                                    <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z"
                                          fill="none" stroke="currentColor" stroke-width="1.8"/>
                                    <circle cx="12" cy="12" r="2.5"
                                            fill="none" stroke="currentColor" stroke-width="1.8"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <label class="remember">
                        <input type="checkbox" name="lembrar" value="1">
                        <span>Lembrar meu e-mail neste dispositivo</span>
                    </label>

                    <button type="submit" class="submit-button">
                        <span>Entrar no sistema</span>
                        <span aria-hidden="true">→</span>
                    </button>
                </form>

                <footer class="card-footer">
                    <p>Problemas para acessar?</p>
                    <span>Entre em contato com o administrador do sistema.</span>
                </footer>
            </div>

            <p class="page-footer">
                © <?= date('Y') ?> SGV9 · Ambiente seguro
            </p>
        </section>
    </main>

    <script src="/assets/js/login.js" defer></script>
</body>
</html>
