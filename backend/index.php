<?php
// Configurações de Conexão
$host = 'db'; 
$db   = 'sgv9';
$user = 'admin';
$pass = 'adminpassword';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$statusConexao = false;
$mensagemErro = '';
$usuarios = [];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $statusConexao = true;
    
    $stmt = $pdo->query('SELECT id, nome, email, data_criacao, ativo FROM usuarios');
    $usuarios = $stmt->fetchAll();
} catch (\PDOException $e) {
    $mensagemErro = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGV9 - Monitoramento de API</title>
    <!-- Bootstrap 5 via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-brand {
            font-weight: 600;
            letter-spacing: 1px;
        }
        .status-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .status-card:hover {
            transform: translateY(-5px);
        }
        .table-container {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-box-seam me-2" viewBox="0 0 16 16">
              <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2zm3.564 1.426L5.596 5 8 5.961 14.154 3.5zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923zM2.623 3.836l2.177-1.275 4.823 1.93-2.177 1.275z"/>
            </svg>
            SGV9 Engine
        </a>
    </div>
</nav>

<div class="container">
    <div class="row mb-4">
        <!-- Card de Status do Banco de Dados -->
        <div class="col-md-6 mb-3">
            <div class="card status-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <?php if($statusConexao): ?>
                            <div class="bg-success text-white rounded-circle p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-database-check" viewBox="0 0 16 16">
                                  <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m1.679-4.493-1.335 2.226a.75.75 0 0 1-1.174.144l-.774-.773a.5.5 0 0 1 .708-.708l.547.548 1.17-1.951a.5.5 0 1 1 .858.514M12.096 8.623L11.5 9.617V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l.317-.525C13.23 9.421 12.353 8 8 8s-5.23 1.421-5.617 2.915A2 2 0 0 0 2.5 11h5.053q-.052-.24-.053-.5t.053-.5q.068-.31.205-.595c.243-.505.626-.957 1.162-1.306zM8 4c-4.353 0-5.23 1.421-5.617 2.915A2 2 0 0 0 2.5 7v1h11V7a2 2 0 0 0-.117-.085C13.23 5.421 12.353 4 8 4"/>
                                  <path d="M2.5 2C3.125 1 5.372 0 8 0s4.875 1 5.5 2C13.5 2.5 12 4 8 4S2.5 2.5 2.5 2"/>
                                </svg>
                            </div>
                        <?php else: ?>
                            <div class="bg-danger text-white rounded-circle p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-database-x" viewBox="0 0 16 16">
                                  <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m-.646-4.854.646.647.646-.647a.5.5 0 0 1 .708.708l-.647.646.647.646a.5.5 0 0 1-.708.708l-.646-.647-.646.647a.5.5 0 0 1-.708-.708l.647-.646-.647-.646a.5.5 0 0 1 .708-.708M8 12c-4.353 0-5.23-1.421-5.617-2.915A2 2 0 0 1 2 9V7h1v2c0 .5.5 1.5 5 1.5s5-1 5-1.5V7h1v2a2 2 0 0 1-.383.085C13.23 10.579 12.353 12 8 12M2.5 4C3.125 3 5.372 2 8 2s4.875 1 5.5 2C13.5 4.5 12 6 8 6S2.5 4.5 2.5 4"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="mb-1">Status do Banco de Dados</h5>
                        <?php if($statusConexao): ?>
                            <p class="mb-0 text-success fw-bold">Online e Operante</p>
                            <small class="text-muted">Conectado ao MySQL 8.4 LTS via PDO</small>
                        <?php else: ?>
                            <p class="mb-0 text-danger fw-bold">Falha na Conexão</p>
                            <small class="text-danger"><?= htmlspecialchars($mensagemErro) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Card de Informações do Ambiente -->
        <div class="col-md-6 mb-3">
            <div class="card status-card h-100">
                <div class="card-body">
                    <h5 class="card-title">Ambiente Docker</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                            Servidor Web
                            <span class="badge bg-primary rounded-pill">Nginx Alpine</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                            Processador
                            <span class="badge bg-primary rounded-pill">PHP 8.2 FPM</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                            Database
                            <span class="badge bg-primary rounded-pill">MySQL 8.4</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php if($statusConexao): ?>
    <div class="row">
        <div class="col-12">
            <div class="table-container">
                <h5 class="mb-3">Usuários do Sistema (Tabela: usuarios)</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Data de Criação</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($usuarios as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($user['nome']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($user['data_criacao']))) ?></td>
                                <td>
                                    <?php if($user['ativo']): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if(empty($usuarios)): ?>
                        <div class="alert alert-warning text-center" role="alert">
                            Nenhum usuário encontrado na base de dados.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <footer class="mt-5 text-center text-muted">
        <small>&copy; <?= date('Y') ?> Projeto SGV9. Motor de Integração.</small>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
