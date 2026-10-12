<?php
declare(strict_types=1);
ob_start();

// Integração por HTTP, em banco isolado criado pelo script clientes.sh.
require '/var/www/html/config/database.php';
require '/var/www/html/config/session.php';
require '/var/www/html/clientes/functions.php';
if (!preg_match('/^sgv9_clientes_test_[0-9]+$/D', getenv('DB_DATABASE') ?: '')) {
    throw new RuntimeException('Este teste exige um banco isolado sgv9_clientes_test_<numero>.');
}
$pdo = getDatabaseConnection();
$checks = 0;
function verificar(bool $ok, string $descricao): void
{
    global $checks;
    if (!$ok) {
        throw new RuntimeException('FALHOU: ' . $descricao);
    }
    $checks++;
    echo 'OK: ' . $descricao . "\n";
}

// Fixtures artificiais: nenhum dado do banco de desenvolvimento é importado.
$pdo->exec("INSERT INTO empresas (id,razao_social,nome_fantasia,cnpj) VALUES
    (901,'Empresa Teste A','Teste A','11.111.111/0001-11'),(902,'Empresa Teste B','Teste B','22.222.222/0001-22')");
$pdo->exec("INSERT INTO perfis (id,nome) VALUES (901,'Teste')");
$stmt = $pdo->prepare('INSERT INTO usuarios (id,empresa_id,perfil_id,nome,email,senha) VALUES (901,901,901,:nome,:email,:senha)');
$stmt->execute(['nome'=>'Usuário Teste','email'=>'teste@example.test','senha'=>password_hash(bin2hex(random_bytes(20)), PASSWORD_DEFAULT)]);
$pdo->exec("INSERT INTO categorias_clientes (id,nome) VALUES (901,'Varejo')");
$pdo->exec("INSERT INTO subcategorias_clientes (id,categoria_id,nome) VALUES (901,901,'Materiais')");
$sessionId = bin2hex(random_bytes(16));
session_id($sessionId);
startSecureSession();
function sessaoTeste(int $empresaId, array $permissoes): void
{
    global $sessionId;
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_id($sessionId);
        session_start();
    }
    $_SESSION = [
        'usuario_id'=>901, 'empresa_id'=>$empresaId, 'usuario_nome'=>'Teste',
        'empresa_nome'=>'Teste', 'perfil_nome'=>'Teste', 'ultima_atividade'=>time(),
        'permissoes'=>$permissoes, 'csrf_token'=>'token-teste-clientes',
    ];
    session_write_close();
}
function requestTeste(string $rota, ?array $dados = null): array
{
    global $sessionId;
    $contexto = stream_context_create(['http'=>[
        'method'=>$dados === null ? 'GET' : 'POST', 'ignore_errors'=>true,
        'follow_location'=>0, 'timeout'=>10,
        'header'=>"Cookie: PHPSESSID=$sessionId\r\nContent-Type: application/x-www-form-urlencoded\r\n",
        'content'=>$dados === null ? '' : http_build_query($dados),
    ]]);
    $body = file_get_contents('http://127.0.0.1:18909' . $rota, false, $contexto);
    $headers = $http_response_header;
    preg_match('/ ([0-9]{3}) /', $headers[0], $matches);
    return ['status'=>(int)$matches[1], 'body'=>$body, 'headers'=>$headers];
}
function flashTeste(): string
{
    global $sessionId;
    session_id($sessionId);
    session_start();
    $mensagem = $_SESSION['flash']['mensagem'] ?? '';
    session_write_close();
    return $mensagem;
}

$pipes = [];
$server = proc_open(['php','-S','127.0.0.1:18909','-t','/var/www/html'],
    [0=>['pipe','r'],1=>['file','/tmp/sgv9-clientes-http.log','a'],2=>['file','/tmp/sgv9-clientes-http.log','a']],$pipes);
if (!is_resource($server)) {
    throw new RuntimeException('Não foi possível iniciar o servidor isolado.');
}
try {
    for ($i=0; $i<50; $i++) {
        $socket = @fsockopen('127.0.0.1',18909);
        if ($socket) { fclose($socket); break; }
        usleep(100000);
    }
    $todas = ['CLIENTES_VISUALIZAR','CLIENTES_GERENCIAR','REPRESENTADAS_VISUALIZAR','LOCALIDADES_VISUALIZAR'];
    sessaoTeste(901,$todas);
    $base = array_map(static fn($v)=>(string)$v, clientePadrao());
    $base = array_replace($base,[
        'csrf_token'=>'token-teste-clientes','nome_fantasia'=>'Cliente <script>alert(1)</script>',
        'razao_social'=>'Comércio Teste','cnpj'=>'11222333000181','subcategoria_id'=>'901',
        'limite_credito'=>'1234,5678','email'=>'contato@example.test',
    ]);
    verificar(requestTeste('/clientes/form.php')['status']===200,'Formulário acessível');
    verificar(requestTeste('/clientes/salvar.php',$base)['status']===302,'Cadastro redireciona');
    $registro = $pdo->query("SELECT * FROM clientes WHERE empresa_id=901 AND nome_fantasia LIKE 'Cliente %'")->fetch();
    verificar((bool)$registro,'Cadastro persistido');
    $id = (int)$registro['id'];
    verificar($registro['cnpj']==='11.222.333/0001-81' && $registro['limite_credito']==='1234.5678','CNPJ normalizado e crédito sem perda decimal');
    verificar((int)$pdo->query("SELECT COUNT(*) FROM logs_usuarios WHERE entidade='clientes' AND registro_id='$id'")->fetchColumn()===1,'Auditoria de criação');
    $lista = requestTeste('/clientes/index.php?q=11222333000181');
    verificar($lista['status']===200 && str_contains($lista['body'],'&lt;script&gt;') && !str_contains($lista['body'],'<script>alert(1)</script>'),'Busca por CNPJ e escaping XSS');
    verificar(requestTeste('/clientes/salvar.php',$base)['status']===302 && str_contains(flashTeste(),'Já existe'),'Duplicidade com CNPJ sem máscara');
    verificar((int)$pdo->query('SELECT COUNT(*) FROM clientes WHERE empresa_id=901')->fetchColumn()===1,'Duplicidade não insere');
    $edicao = array_replace($base,['id'=>(string)$id,'nome_fantasia'=>'Cliente Editado','email'=>'']);
    requestTeste('/clientes/salvar.php',$edicao);
    verificar($pdo->query("SELECT nome_fantasia FROM clientes WHERE id=$id")->fetchColumn()==='Cliente Editado','Edição mantém ID e persiste');
    $auditoria = $pdo->query("SELECT dados_anteriores FROM logs_usuarios WHERE acao='CLIENTE_ATUALIZADO' AND registro_id='$id'")->fetchColumn();
    verificar(str_contains((string)$auditoria,'<script>'),'Auditoria preserva dados anteriores');

    foreach ([
        ['cnpj'=>'00000000000000'],['cnpj'=>'abc11222333000181'],['email'=>'email-invalido'],
        ['data_cadastro'=>'2026-02-30'],['nome_fantasia'=>''],['nome_fantasia'=>str_repeat('á',201)],
        ['limite_credito'=>'-1'],['limite_credito'=>'1e5'],['limite_credito'=>'1.000,00'],
        ['limite_credito'=>'1.12345'],['subcategoria_id'=>'999999'],['subcategoria_id'=>'901x'],
        ['nome_fantasia'=>['array']],['email'=>['array']],['id'=>'-1'],['id'=>'1abc'],['id'=>['array']],['ativo'=>'2'],
    ] as $invalido) {
        requestTeste('/clientes/salvar.php',array_replace($edicao,$invalido));
        verificar($pdo->query("SELECT nome_fantasia FROM clientes WHERE id=$id")->fetchColumn()==='Cliente Editado',
            'Entrada inválida rejeitada: '.json_encode(array_keys($invalido)));
        verificar(!str_contains(flashTeste(),'sucesso'),'Validação apresenta erro');
    }
    requestTeste('/clientes/salvar.php',array_replace($edicao,['csrf_token'=>'errado','nome_fantasia'=>'CSRF']));
    verificar(str_contains(flashTeste(),'Requisição inválida'),'CSRF rejeitado');
    requestTeste('/clientes/salvar.php',array_replace($edicao,['csrf_token'=>['array']]));
    verificar(str_contains(flashTeste(),'Requisição inválida'),'CSRF array rejeitado sem erro PHP');
    requestTeste('/clientes/salvar.php');
    verificar(str_contains(flashTeste(),'Requisição inválida'),'GET não salva cliente');
    requestTeste('/clientes/alterar-status.php',['csrf_token'=>'errado','id'=>(string)$id,'ativo'=>'0']);
    verificar((int)$pdo->query("SELECT ativo FROM clientes WHERE id=$id")->fetchColumn()===1
        && str_contains(flashTeste(),'Requisição inválida'),'CSRF de status rejeitado');
    requestTeste('/clientes/alterar-status.php');
    verificar(str_contains(flashTeste(),'Requisição inválida'),'GET não altera status');
    $pdo->exec("UPDATE clientes SET atualizado_em='2020-01-01 00:00:00' WHERE id=$id");
    requestTeste('/clientes/alterar-status.php',['csrf_token'=>'token-teste-clientes','id'=>(string)$id,'ativo'=>'0']);
    verificar((int)$pdo->query("SELECT ativo FROM clientes WHERE id=$id")->fetchColumn()===0,'Inativação lógica');
    verificar($pdo->query("SELECT atualizado_em FROM clientes WHERE id=$id")->fetchColumn()>'2020-01-01 00:00:00','Timestamp atualizado pelo banco');
    verificar(!str_contains(requestTeste('/clientes/index.php')['body'],'Cliente Editado'),'Filtro padrão omite inativos');
    verificar(str_contains(requestTeste('/clientes/index.php?status=inativos')['body'],'Cliente Editado'),'Filtro de inativos');
    $logs = (int)$pdo->query("SELECT COUNT(*) FROM logs_usuarios WHERE registro_id='$id' AND entidade='clientes'")->fetchColumn();
    requestTeste('/clientes/alterar-status.php',['csrf_token'=>'token-teste-clientes','id'=>(string)$id,'ativo'=>'0']);
    verificar((int)$pdo->query("SELECT COUNT(*) FROM logs_usuarios WHERE registro_id='$id' AND entidade='clientes'")->fetchColumn()===$logs,'Status repetido não duplica auditoria');
    requestTeste('/clientes/alterar-status.php',['csrf_token'=>'token-teste-clientes','id'=>(string)$id,'ativo'=>'1']);
    verificar((int)$pdo->query("SELECT ativo FROM clientes WHERE id=$id")->fetchColumn()===1,'Reativação');
    foreach (['0x','-1',['array']] as $valor) {
        requestTeste('/clientes/alterar-status.php',['csrf_token'=>'token-teste-clientes','id'=>(string)$id,'ativo'=>$valor]);
        verificar((int)$pdo->query("SELECT ativo FROM clientes WHERE id=$id")->fetchColumn()===1,'Status inválido rejeitado');
    }
    sessaoTeste(902,$todas);
    verificar(!str_contains(requestTeste('/clientes/index.php?status=todos')['body'],'Cliente Editado'),'Listagem isolada por empresa');
    verificar(requestTeste('/clientes/form.php?id='.$id)['status']===404,'Formulário de outra empresa bloqueado');
    requestTeste('/clientes/salvar.php',array_replace($edicao,['nome_fantasia'=>'Outra Empresa']));
    requestTeste('/clientes/alterar-status.php',['csrf_token'=>'token-teste-clientes','id'=>(string)$id,'ativo'=>'0']);
    verificar($pdo->query("SELECT nome_fantasia FROM clientes WHERE id=$id")->fetchColumn()==='Cliente Editado'
        && (int)$pdo->query("SELECT ativo FROM clientes WHERE id=$id")->fetchColumn()===1,'Escrita de outra empresa bloqueada');
    // O usuário de auditoria precisa pertencer ao contexto de empresa da fixture.
    $pdo->exec('UPDATE usuarios SET empresa_id=902 WHERE id=901');
    requestTeste('/clientes/salvar.php',array_replace($base,['nome_fantasia'=>'Cliente Empresa B']));
    verificar((int)$pdo->query('SELECT COUNT(*) FROM clientes WHERE empresa_id=902')->fetchColumn()===1,'Mesmo CNPJ permitido em outra empresa');
    sessaoTeste(901,['CLIENTES_VISUALIZAR']);
    $somenteLeitura = requestTeste('/clientes/index.php');
    verificar($somenteLeitura['status']===200 && !str_contains($somenteLeitura['body'],'+ Novo cliente')
        && !str_contains($somenteLeitura['body'],'action="/clientes/alterar-status.php"'),'Usuário de leitura sem ações de escrita');
    verificar(requestTeste('/clientes/form.php')['status']===403,'Permissão do formulário');
    verificar(requestTeste('/clientes/salvar.php',$edicao)['status']===403,'Permissão de salvar');
    verificar(requestTeste('/clientes/alterar-status.php',['id'=>(string)$id])['status']===403,'Permissão de status');
    sessaoTeste(901,[]);
    verificar(requestTeste('/clientes/index.php')['status']===403,'Permissão de visualizar');
    sessaoTeste(901,$todas);
    verificar(requestTeste('/clientes/form.php?id=1abc')['status']===400,'GET com ID malformado');
    verificar(requestTeste('/clientes/form.php?id[]=1')['status']===400,'GET com ID array');
    verificar(requestTeste('/clientes/form.php?id=999999')['status']===404,'GET com ID inexistente');
    verificar(requestTeste('/clientes/index.php?q[]=x&status[]=x')['status']===200,'Filtros array tratados');
    if (getenv('SGV9_TEST_SCHEMA') !== 'novo') {
        verificar(requestTeste('/dashboard.php')['status']===200,'Regressão do dashboard');
        verificar(requestTeste('/localidades/index.php')['status']===200,'Regressão da listagem de Localidades');
        verificar(requestTeste('/representadas/index.php')['status']===200,'Regressão da listagem de Representadas');
    }

    // Simula falha de auditoria: a transação deve desfazer a atualização.
    sessaoTeste(901,$todas);
    session_id($sessionId); session_start(); $_SESSION['usuario_id']=999999; session_write_close();
    requestTeste('/clientes/salvar.php',array_replace($edicao,['nome_fantasia'=>'Falha de auditoria']));
    verificar($pdo->query("SELECT nome_fantasia FROM clientes WHERE id=$id")->fetchColumn()==='Cliente Editado','Rollback quando auditoria falha');
    session_id($sessionId); session_start(); $_SESSION=[]; session_write_close();
    verificar(requestTeste('/clientes/index.php')['status']===302,'Acesso sem sessão redireciona para login');
    echo "PASSOU: $checks verificações\n";
} finally {
    proc_terminate($server);
    proc_close($server);
    session_id($sessionId); session_start(); session_destroy();
}
