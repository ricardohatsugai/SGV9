<?php
declare(strict_types=1);

require '/var/www/html/config/database.php';
require '/var/www/html/config/session.php';
require '/var/www/html/clientes/functions.php';

$pdo = getDatabaseConnection();
$banco = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
if (!preg_match('/^sgv9_test_clientes_[0-9a-f]{12}_(local|versionado)$/D', $banco)) {
    throw new RuntimeException('Testes permitidos somente em banco exclusivo.');
}
$pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));
$pdo->exec("INSERT INTO empresas (id, razao_social, nome_fantasia, cnpj) VALUES
    (1, 'Empresa teste A', 'Empresa A', '11.222.333/0001-81'),
    (2, 'Empresa teste B', 'Empresa B', '04.252.011/0001-10')");
$pdo->exec("INSERT INTO perfis (id,nome) VALUES (1,'ADMINISTRADOR'),(2,'CONSULTA'),(3,'VENDEDOR'),(4,'ASSISTENTE')");
$stmt = $pdo->prepare('INSERT INTO usuarios (id, empresa_id, perfil_id, nome, email, senha) VALUES (1, 1, 1, :nome, :email, :senha)');
$stmt->execute(['nome' => 'Teste', 'email' => 'teste@example.invalid', 'senha' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT)]);
$estrutura = estruturaClientes($pdo);
$pdo->exec('INSERT INTO `' . $estrutura['categorias'] . "` (id, nome) VALUES (1, 'Varejo')");
$pdo->exec('INSERT INTO `' . $estrutura['subcategorias'] . "` (id, categoria_id, nome) VALUES (1, 1, 'Ferragens')");
$migration = file_get_contents(__DIR__ . '/migration.sql');
$pdo->exec($migration);
$contagemPermissoes = (int) $pdo->query('SELECT COUNT(*) FROM perfil_permissoes')->fetchColumn();
$pdo->exec($migration);
$verificacoes = 0;
function conferir(bool $condicao, string $descricao): void
{
    global $verificacoes;
    if (!$condicao) { throw new RuntimeException('FALHA: ' . $descricao); }
    $verificacoes++;
}
function recusar(callable $operacao, string $descricao): void
{
    try { $operacao(); } catch (InvalidArgumentException $erro) { conferir(true, $descricao); return; }
    throw new RuntimeException('FALHA: operação aceita: ' . $descricao);
}
conferir((int) $pdo->query('SELECT COUNT(*) FROM perfil_permissoes')->fetchColumn() === $contagemPermissoes, 'migration reexecutável');
conferir($contagemPermissoes === 6, 'perfis previstos na migration');
$entrada = [
    'id' => '0', 'nome_fantasia' => '<script>alert(1)</script> Cliente teste',
    'razao_social' => 'Cliente de teste Ltda', 'cnpj' => '11222333000181',
    'data_cadastro' => '2026-10-12', 'subcategoria_id' => '1',
    'email' => 'cliente@example.invalid', 'limite_credito' => '1234,5678', 'ativo' => '1',
];
$id = salvarCliente($pdo, $estrutura, $entrada, 1, 1);
$cliente = carregarCliente($pdo, $estrutura, $id, 1);
conferir($cliente['cnpj'] === '11.222.333/0001-81', 'normalização de CNPJ');
conferir($cliente['limite_credito'] === '1234.5678', 'precisão decimal');
conferir(validarDadosCliente(array_replace($entrada, ['limite_credito' => '999999999999999,9999']), $estrutura)['limite_credito'] === '999999999999999.9999', 'validação preserva precisão máxima');
conferir((int) $pdo->query('SELECT COUNT(*) FROM logs_usuarios')->fetchColumn() === 1, 'auditoria de criação');
recusar(fn() => salvarCliente($pdo, $estrutura, $entrada, 1, 1), 'CNPJ duplicado por empresa');
$outro = salvarCliente($pdo, $estrutura, $entrada, 2, 1);
conferir(carregarCliente($pdo, $estrutura, $outro, 1) === null, 'isolamento entre empresas');
foreach ([['cnpj', '11111111111111'], ['email', 'invalido'], ['data_cadastro', '2026-02-30'],
    ['limite_credito', '-1'], ['limite_credito', '1.234,56'], ['limite_credito', '1,00001'],
    ['limite_credito', '1000000000000000'], ['nome_fantasia', str_repeat('x', 201)],
    ['subcategoria_id', '1abc'], ['nome_fantasia', ['array']], ['ativo', '2']] as [$campo, $valor]) {
    $invalido = array_replace($entrada, [$campo => $valor]);
    recusar(fn() => validarDadosCliente($invalido, $estrutura), 'validação de ' . $campo);
}
recusar(fn() => salvarCliente($pdo, $estrutura, array_replace($entrada, ['subcategoria_id' => '999']), 1, 1), 'subcategoria inexistente');
$edicao = array_replace($entrada, ['id' => (string) $id, 'versao' => versaoCliente($cliente), 'razao_social' => 'Razão social editada']);
salvarCliente($pdo, $estrutura, $edicao, 1, 1);
conferir(carregarCliente($pdo, $estrutura, $id, 1)['razao_social'] === 'Razão social editada', 'edição persistida');
recusar(fn() => salvarCliente($pdo, $estrutura, $edicao, 1, 1), 'edição concorrente desatualizada');
recusar(fn() => salvarCliente($pdo, $estrutura, $edicao, 2, 1), 'edição em outra empresa');
$cliente = carregarCliente($pdo, $estrutura, $id, 1);
$status = ['id' => (string) $id, 'versao' => versaoCliente($cliente), 'ativo' => '0'];
alterarStatusCliente($pdo, $estrutura, $status, 1, 1);
conferir((int) carregarCliente($pdo, $estrutura, $id, 1)['ativo'] === 0, 'inativação preserva registro');
recusar(fn() => alterarStatusCliente($pdo, $estrutura, $status, 1, 1), 'status concorrente');
$cliente = carregarCliente($pdo, $estrutura, $id, 1);
alterarStatusCliente($pdo, $estrutura, ['id' => (string) $id, 'versao' => versaoCliente($cliente), 'ativo' => '1'], 1, 1);
conferir((int) carregarCliente($pdo, $estrutura, $id, 1)['ativo'] === 1, 'reativação');
$credito = $estrutura['campos']['limite_credito'];
$pdo->exec("UPDATE clientes SET `$credito` = 999999999999999.9999 WHERE id = $id");
conferir(carregarCliente($pdo, $estrutura, $id, 1)['limite_credito'] === '999999999999999.9999', 'limite DECIMAL(19,4)');
$cliente = carregarCliente($pdo, $estrutura, $id, 1);
$edicao = array_replace($edicao, ['versao' => versaoCliente($cliente), 'razao_social' => 'Não deve persistir']);
// Usuário inexistente viola a FK da auditoria depois do UPDATE do cliente.
try { salvarCliente($pdo, $estrutura, $edicao, 1, 999); throw new RuntimeException('Falha simulada não ocorreu'); }
catch (PDOException $erro) { conferir(carregarCliente($pdo, $estrutura, $id, 1)['razao_social'] !== 'Não deve persistir', 'rollback quando auditoria falha'); }

// Servidor de teste isolado, usando exclusivamente este banco e sessões temporárias.
$pastaSessoes = __DIR__ . '/sessoes_' . $banco;
mkdir($pastaSessoes);
ini_set('session.save_path', $pastaSessoes);
$sessaoAdmin = bin2hex(random_bytes(16));
$token = bin2hex(random_bytes(32));
session_id($sessaoAdmin);
session_start();
$_SESSION = ['usuario_id' => 1, 'empresa_id' => 1, 'usuario_nome' => 'Teste',
    'perfil_nome' => 'ADMINISTRADOR', 'empresa_nome' => 'Empresa A', 'csrf_token' => $token,
    'permissoes' => ['CLIENTES_VISUALIZAR', 'CLIENTES_GERENCIAR']];
session_write_close();
$sessaoConsulta = bin2hex(random_bytes(16));
session_id($sessaoConsulta);
session_start();
$_SESSION = ['usuario_id' => 1, 'empresa_id' => 1, 'usuario_nome' => 'Teste',
    'perfil_nome' => 'CONSULTA', 'empresa_nome' => 'Empresa A', 'csrf_token' => $token,
    'permissoes' => ['CLIENTES_VISUALIZAR']];
session_write_close();
$socket = stream_socket_server('tcp://127.0.0.1:0');
$endereco = stream_socket_get_name($socket, false);
fclose($socket);
$processo = proc_open([PHP_BINARY, '-d', 'session.save_path=' . $pastaSessoes, '-S', $endereco, '-t', '/var/www/html'],
    [0 => ['pipe', 'r'], 1 => ['file', __DIR__ . '/http.log', 'a'], 2 => ['file', __DIR__ . '/http.log', 'a']], $pipes);
if (!is_resource($processo)) { throw new RuntimeException('Servidor de teste indisponível.'); }
try {
    for ($tentativa = 0; $tentativa < 50; $tentativa++) {
        $conexao = @stream_socket_client('tcp://' . $endereco, $errno, $errstr, 0.1);
        if ($conexao) { fclose($conexao); break; }
        usleep(50000);
    }
    $requisicao = function (string $rota, ?string $sessao, ?array $post = null) use ($endereco): array {
        $headers = "Connection: close\r\n" . ($sessao ? 'Cookie: PHPSESSID=' . $sessao . "\r\n" : '');
        $opcoes = ['method' => $post === null ? 'GET' : 'POST', 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 5, 'header' => $headers];
        if ($post !== null) {
            $opcoes['header'] .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $opcoes['content'] = http_build_query($post);
        }
        $html = file_get_contents('http://' . $endereco . $rota, false, stream_context_create(['http' => $opcoes]));
        return ['status' => (int) substr($http_response_header[0], 9, 3), 'html' => $html, 'headers' => $http_response_header];
    };
    conferir($requisicao('/clientes/index.php', null)['status'] === 302, 'autenticação obrigatória');
    $lista = $requisicao('/clientes/index.php', $sessaoAdmin);
    conferir($lista['status'] === 200 && str_contains($lista['html'], '&lt;script&gt;alert(1)&lt;/script&gt;'), 'listagem com escaping de XSS');
    conferir(!str_contains($lista['html'], '<script>alert(1)</script>'), 'sem execução de dados do cliente');
    $busca = $requisicao('/clientes/index.php?q=11222333000181', $sessaoAdmin);
    conferir($busca['status'] === 200 && str_contains($busca['html'], 'Razão social editada'), 'busca com prepared statements nativos');
    conferir($requisicao('/clientes/index.php?q%5B%5D=x', $sessaoAdmin)['status'] === 400, 'pesquisa malformada');
    conferir($requisicao('/clientes/form.php?id=1abc', $sessaoAdmin)['status'] === 400, 'ID malformado');
    conferir($requisicao('/clientes/form.php?id=' . $outro, $sessaoAdmin)['status'] === 404, 'formulário não expõe outra empresa');
    conferir($requisicao('/clientes/form.php', $sessaoConsulta)['status'] === 403, 'consulta não pode editar');
    $antes = (int) $pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn();
    conferir($requisicao('/clientes/salvar.php', $sessaoConsulta, $entrada + ['csrf_token' => $token])['status'] === 403, 'consulta não pode gravar');
    $requisicao('/clientes/salvar.php', $sessaoAdmin, $entrada + ['csrf_token' => 'invalido']);
    $requisicao('/clientes/salvar.php', $sessaoAdmin, $entrada + ['csrf_token' => ['malformado']]);
    conferir((int) $pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn() === $antes, 'CSRF inválido não grava');
    $atual = carregarCliente($pdo, $estrutura, $id, 1);
    $statusInvalido = ['id' => (string) $id, 'ativo' => '0', 'versao' => versaoCliente($atual), 'csrf_token' => 'invalido'];
    $requisicao('/clientes/alterar-status.php', $sessaoAdmin, $statusInvalido);
    conferir((int) carregarCliente($pdo, $estrutura, $id, 1)['ativo'] === 1, 'CSRF inválido não altera status');
    conferir($requisicao('/clientes/alterar-status.php', $sessaoConsulta, $statusInvalido)['status'] === 403, 'consulta não altera status');
    $requisicao('/clientes/alterar-status.php', $sessaoAdmin);
    conferir((int) carregarCliente($pdo, $estrutura, $id, 1)['ativo'] === 1, 'GET não altera status');
    conferir($requisicao('/clientes/index.php?q=' . urlencode("' OR 1=1 --"), $sessaoAdmin)['status'] === 200, 'entrada SQL tratada como texto');
    $formulario = $requisicao('/clientes/form.php?id=' . $id, $sessaoAdmin);
    conferir($formulario['status'] === 200 && str_contains($formulario['html'], 'Categoria / Subcategoria'), 'formulário de edição');
    $atual = carregarCliente($pdo, $estrutura, $id, 1);
    $post = array_replace($edicao, ['versao' => versaoCliente($atual), 'csrf_token' => $token, 'razao_social' => 'Persistido via HTTP']);
    conferir($requisicao('/clientes/salvar.php', $sessaoAdmin, $post)['status'] === 302, 'salvar pela rota HTTP');
    conferir(carregarCliente($pdo, $estrutura, $id, 1)['razao_social'] === 'Persistido via HTTP', 'persistência pela rota');
    $lista = $requisicao('/clientes/index.php', $sessaoAdmin);
    preg_match('/name="versao" value="([0-9a-f]{64})"/', $lista['html'], $matches);
    conferir(isset($matches[1]), 'versão publicada na listagem');
    $requisicao('/clientes/alterar-status.php', $sessaoAdmin, ['id' => (string) $id, 'ativo' => '0', 'versao' => $matches[1], 'csrf_token' => $token]);
    conferir((int) carregarCliente($pdo, $estrutura, $id, 1)['ativo'] === 0, 'status pela rota e versão da listagem');
    conferir(!str_contains($requisicao('/clientes/index.php', $sessaoAdmin)['html'], 'Persistido via HTTP'), 'filtro de ativos');
    conferir(str_contains($requisicao('/clientes/index.php?status=inativos', $sessaoAdmin)['html'], 'Persistido via HTTP'), 'filtro de inativos');
    $post['cnpj'] = 'invalido';
    $post['nome_fantasia'] = 'Dados preservados após erro';
    $requisicao('/clientes/salvar.php', $sessaoAdmin, $post);
    conferir(str_contains($requisicao('/clientes/form.php?id=' . $id, $sessaoAdmin)['html'], 'Dados preservados após erro'), 'preservação do formulário após validação');
    for ($indice = 1; $indice <= 26; $indice++) {
        $documento = '12345678' . str_pad((string) $indice, 4, '0', STR_PAD_LEFT);
        foreach ([[5,4,3,2,9,8,7,6,5,4,3,2], [6,5,4,3,2,9,8,7,6,5,4,3,2]] as $pesos) {
            $soma = 0;
            foreach ($pesos as $posicao => $peso) { $soma += (int) $documento[$posicao] * $peso; }
            $resto = $soma % 11;
            $documento .= (string) ($resto < 2 ? 0 : 11 - $resto);
        }
        salvarCliente($pdo, $estrutura, array_replace($entrada, ['cnpj' => $documento, 'nome_fantasia' => 'ZZ Paginação ' . $indice]), 1, 1);
    }
    $paginaDois = $requisicao('/clientes/index.php?status=todos&q=ZZ%20Pagina&pagina=2', $sessaoAdmin);
    conferir($paginaDois['status'] === 200 && str_contains($paginaDois['html'], 'Página 2 de 2'), 'paginação da pesquisa');
    conferir(substr_count($paginaDois['html'], 'class="action-form"') === 1, 'limite de 25 registros por página');
    conferir(str_contains($paginaDois['html'], 'q=ZZ+Pagina&amp;status=todos&amp;pagina=1'), 'paginação preserva filtros');
    conferir(!str_contains(file_get_contents(__DIR__ . '/http.log'), 'Fatal error'), 'sem erro fatal nas rotas');
} finally {
    proc_terminate($processo);
    proc_close($processo);
}
echo $verificacoes, " verificações aprovadas\n";
