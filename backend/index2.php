<?php
// Configuração do Banco de Dados
// O "host" não é localhost. É o nome do serviço do banco na rede Docker (neste caso, "db")
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

try {
    // 1. Instancia a conexão
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // 2. Executa a DQL de teste validando se as tabelas foram criadas
    $stmt = $pdo->query('SELECT id, nome, email, data_criacao FROM usuarios');
    $usuarios = $stmt->fetchAll();

    // 3. Imprime o resultado como JSON (Estrutura padrão de uma API REST)
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'Conexao Bem Sucedida',
        'usuarios_cadastrados' => $usuarios
    ], JSON_PRETTY_PRINT);

} catch (\PDOException $e) {
    header('Content-Type: application/json', true, 500);
    echo json_encode([
        'status' => 'Erro Conexao Database',
        'erro' => $e->getMessage()
    ]);
}
?>
