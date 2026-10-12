<?php
// Executado pelo harness de Clientes, no banco isolado e servidor HTTP já iniciados.
$pdo->exec('UPDATE usuarios SET empresa_id=901 WHERE id=901');
sessaoTeste(901,$todas);
$pdo->exec("INSERT INTO estados(id,nome,sigla,ativo) VALUES(901,'Estado teste','XT',1),(902,'Estado inativo','XI',0)");
$pdo->exec("INSERT INTO cidades(id,nome,estado_id,ativo) VALUES(901,'Cidade teste',901,1),(902,'Cidade inativa',901,0),(903,'Cidade estado inativo',902,1)");
$pdo->exec("INSERT INTO bairros(id,nome,cidade_id,ativo) VALUES(901,'Bairro teste',901,1),(902,'Bairro inativo',901,0),(903,'Outro bairro',901,1),(904,'Bairro cidade inativa',902,1),(905,'Bairro estado inativo',903,1)");
$tipoEndereco=(string)$pdo->query("SELECT id FROM tipos_endereco WHERE tipo='ENTREGA'")->fetchColumn();
$departamento=(string)$pdo->query("SELECT id FROM departamentos_clientes WHERE nome='COMPRAS'")->fetchColumn();
$destino='/clientes/detalhes.php?id='.$id;
$salvarRelacionado='/clientes/salvar-relacionado.php';
$statusRelacionado='/clientes/alterar-status-relacionado.php';
$endereco=[
    'csrf_token'=>'token-teste-clientes','tipo'=>'endereco','cliente_id'=>(string)$id,'id'=>'0',
    'tipo_endereco_id'=>$tipoEndereco,'bairro_id'=>'901','logradouro'=>'Rua <script>teste</script>',
    'numero'=>'10','complemento'=>'Sala 1','cep'=>'12345678','contato'=>'Pessoa Teste',
    'telefone'=>'123','celular'=>'456','email'=>'endereco@example.test','observacao'=>'Obs <b>teste</b>',
    'ativo'=>'1','principal'=>'1',
];
verificar(requestTeste($destino)['status']===200,'Detalhes acessíveis');
verificar(requestTeste('/clientes/relacionado-form.php?tipo=endereco&cliente_id='.$id)['status']===200,'Formulário de endereço');
requestTeste($salvarRelacionado,$endereco);
$endereco1=$pdo->query("SELECT * FROM enderecos_clientes WHERE cliente_id=$id")->fetch();
verificar((bool)$endereco1 && $endereco1['cep']==='12345-678','Endereço persistido e CEP normalizado');
$eid1=(int)$endereco1['id'];
verificar((int)$endereco1['principal']===1,'Primeiro endereço principal');
verificar(str_contains(requestTeste($destino)['body'],'&lt;script&gt;teste&lt;/script&gt;'),'Endereço recebe escaping');
$segundo=array_replace($endereco,['logradouro'=>'Rua Secundária','bairro_id'=>'903']);
requestTeste($salvarRelacionado,$segundo);
$eid2=(int)$pdo->query("SELECT id FROM enderecos_clientes WHERE cliente_id=$id AND logradouro='Rua Secundária'")->fetchColumn();
verificar($eid2>0 && (int)$pdo->query("SELECT principal FROM enderecos_clientes WHERE id=$eid1")->fetchColumn()===0,'Troca do principal preserva o anterior');
verificar((int)$pdo->query("SELECT COUNT(*) FROM enderecos_clientes WHERE cliente_id=$id AND principal=1 AND ativo=1")->fetchColumn()===1,'Um principal ativo');
try {
    $pdo->exec("UPDATE enderecos_clientes SET principal=1 WHERE id=$eid1");
    verificar(false,'Constraint deveria bloquear dois principais');
} catch(PDOException $erro) { verificar(($erro->errorInfo[1] ?? null)===1062,'Banco protege único principal ativo'); }
$log=$pdo->query("SELECT dados_novos FROM logs_usuarios WHERE acao='ENDERECO_CLIENTE_CRIADO' AND registro_id='$id' ORDER BY id DESC LIMIT 1")->fetchColumn();
verificar(str_contains($log,'principal_substituido') && str_contains($log,(string)$eid1),'Auditoria registra principal substituído');
$editarEndereco=array_replace($endereco,['id'=>(string)$eid1,'logradouro'=>'Rua Editada']);
unset($editarEndereco['principal']);
requestTeste($salvarRelacionado,$editarEndereco);
verificar($pdo->query("SELECT logradouro FROM enderecos_clientes WHERE id=$eid1")->fetchColumn()==='Rua Editada','Edição de endereço preserva ID');
foreach([
    ['cep'=>'12'],['cep'=>'12345-67x'],['logradouro'=>''],['logradouro'=>str_repeat('x',201)],
    ['bairro_id'=>'999999'],['bairro_id'=>'902'],['bairro_id'=>'904'],['bairro_id'=>'905'],
    ['bairro_id'=>'901x'],['tipo_endereco_id'=>'999999'],['email'=>'invalido'],['email'=>['array']],
    ['id'=>'1x'],['cliente_id'=>['array']],['tipo'=>'clientes;DROP'],['principal'=>'2'],['ativo'=>'2'],
] as $invalido) {
    $antes=(int)$pdo->query('SELECT COUNT(*) FROM enderecos_clientes')->fetchColumn();
    requestTeste($salvarRelacionado,array_replace($endereco,$invalido));
    verificar((int)$pdo->query('SELECT COUNT(*) FROM enderecos_clientes')->fetchColumn()===$antes
        && !str_contains(flashTeste(),'sucesso'),'Validação de endereço: '.json_encode(array_keys($invalido)));
}
requestTeste($salvarRelacionado,array_replace($editarEndereco,['csrf_token'=>'errado','logradouro'=>'Inválido']));
verificar($pdo->query("SELECT logradouro FROM enderecos_clientes WHERE id=$eid1")->fetchColumn()==='Rua Editada','CSRF bloqueia edição de endereço');
requestTeste($statusRelacionado,['tipo'=>'endereco','cliente_id'=>(string)$id,'id'=>(string)$eid2,'ativo'=>'0','csrf_token'=>'token-teste-clientes']);
verificar((int)$pdo->query("SELECT ativo FROM enderecos_clientes WHERE id=$eid2")->fetchColumn()===0,'Inativação de endereço');
requestTeste($salvarRelacionado,array_replace($editarEndereco,['principal'=>'1']));
requestTeste($statusRelacionado,['tipo'=>'endereco','cliente_id'=>(string)$id,'id'=>(string)$eid2,'ativo'=>'1','csrf_token'=>'token-teste-clientes']);
verificar((int)$pdo->query("SELECT ativo FROM enderecos_clientes WHERE id=$eid2")->fetchColumn()===0
    && str_contains(flashTeste(),'outro endereço principal'),'Reativação recusa conflito de principal');
// Edição explícita resolve conflito sem apagar o endereço.
$editarSegundo=array_replace($segundo,['id'=>(string)$eid2]); unset($editarSegundo['principal']);
requestTeste($salvarRelacionado,$editarSegundo);
verificar((int)$pdo->query("SELECT ativo FROM enderecos_clientes WHERE id=$eid2")->fetchColumn()===1,'Reativação pela edição com principal desmarcado');
$pdo->exec('UPDATE bairros SET ativo=0 WHERE id=901');
$form=requestTeste('/clientes/relacionado-form.php?tipo=endereco&cliente_id='.$id.'&id='.$eid1);
verificar(str_contains($form['body'],'localidade inativa'),'Edição preserva opção histórica inativa');
$novoForm=requestTeste('/clientes/relacionado-form.php?tipo=endereco&cliente_id='.$id);
verificar(!str_contains($novoForm['body'],'value="901" selected') && !str_contains($novoForm['body'],'Bairro teste —'),'Novo formulário omite bairro inativo');
requestTeste($salvarRelacionado,array_replace($editarEndereco,['logradouro'=>'Histórico editado']));
verificar($pdo->query("SELECT logradouro FROM enderecos_clientes WHERE id=$eid1")->fetchColumn()==='Histórico editado','Edição mantém localidade histórica');
$inativar=['csrf_token'=>'token-teste-clientes','tipo'=>'endereco','cliente_id'=>(string)$id,'id'=>(string)$eid1,'ativo'=>'0'];
requestTeste($statusRelacionado,$inativar);
requestTeste($statusRelacionado,array_replace($inativar,['ativo'=>'1']));
verificar((int)$pdo->query("SELECT ativo FROM enderecos_clientes WHERE id=$eid1")->fetchColumn()===0,'Status recusa reativação em localidade inativa');
requestTeste($salvarRelacionado,array_replace($editarEndereco,['logradouro'=>'Bypass reativação']));
verificar((int)$pdo->query("SELECT ativo FROM enderecos_clientes WHERE id=$eid1")->fetchColumn()===0,'Edição também recusa reativação em localidade inativa');
requestTeste($salvarRelacionado,array_replace($editarEndereco,['bairro_id'=>'903','principal'=>'1']));
verificar((int)$pdo->query("SELECT ativo FROM enderecos_clientes WHERE id=$eid1")->fetchColumn()===1,'Reativação após corrigir localidade');

$contato=['csrf_token'=>'token-teste-clientes','tipo'=>'contato','cliente_id'=>(string)$id,'id'=>'0',
    'departamento_id'=>$departamento,'nome'=>'Pessoa <script>teste</script>','telefone'=>'123',
    'celular'=>'456','email'=>'contato@example.test','observacao'=>'Contato teste','ativo'=>'1'];
requestTeste($salvarRelacionado,$contato);
$cid=(int)$pdo->query("SELECT id FROM contatos_clientes WHERE cliente_id=$id")->fetchColumn();
verificar($cid>0,'Contato persistido');
verificar(str_contains(requestTeste($destino.'&aba=contatos')['body'],'Pessoa &lt;script&gt;'),'Contato recebe escaping');
requestTeste($salvarRelacionado,$contato);
verificar((int)$pdo->query("SELECT COUNT(*) FROM contatos_clientes WHERE cliente_id=$id")->fetchColumn()===1
    && str_contains(flashTeste(),'Já existe'),'Contato duplicado rejeitado');
requestTeste($salvarRelacionado,array_replace($contato,['nome'=>'Outra Pessoa']));
verificar((int)$pdo->query("SELECT COUNT(*) FROM contatos_clientes WHERE cliente_id=$id")->fetchColumn()===2,'Múltiplos contatos');
$editarContato=array_replace($contato,['id'=>(string)$cid,'nome'=>'Pessoa Editada']);
requestTeste($salvarRelacionado,$editarContato);
verificar($pdo->query("SELECT nome FROM contatos_clientes WHERE id=$cid")->fetchColumn()==='Pessoa Editada','Contato editado');
foreach([['nome'=>''],['nome'=>str_repeat('x',101)],['departamento_id'=>'999999'],['departamento_id'=>'abc'],
    ['email'=>'invalido'],['telefone'=>str_repeat('1',21)],['observacao'=>str_repeat('x',65536)],['nome'=>['array']]] as $invalido) {
    requestTeste($salvarRelacionado,array_replace($editarContato,$invalido));
    verificar($pdo->query("SELECT nome FROM contatos_clientes WHERE id=$cid")->fetchColumn()==='Pessoa Editada'
        && !str_contains(flashTeste(),'sucesso'),'Validação de contato: '.json_encode(array_keys($invalido)));
}
$statusContato=['csrf_token'=>'token-teste-clientes','tipo'=>'contato','cliente_id'=>(string)$id,'id'=>(string)$cid,'ativo'=>'0'];
requestTeste($statusRelacionado,$statusContato);
verificar((int)$pdo->query("SELECT ativo FROM contatos_clientes WHERE id=$cid")->fetchColumn()===0,'Contato inativado');
verificar(!str_contains(requestTeste($destino.'&aba=contatos')['body'],'Pessoa Editada')
    && str_contains(requestTeste($destino.'&aba=contatos&status=inativos')['body'],'Pessoa Editada'),'Filtros de contatos');
requestTeste($salvarRelacionado,array_replace($contato,['nome'=>'Pessoa Editada']));
verificar(str_contains(flashTeste(),'inclusive entre os inativos'),'Duplicidade inclui contato inativo');
requestTeste($statusRelacionado,array_replace($statusContato,['ativo'=>'1']));
verificar((int)$pdo->query("SELECT ativo FROM contatos_clientes WHERE id=$cid")->fetchColumn()===1,'Contato reativado');
requestTeste($statusRelacionado,array_replace($statusContato,['csrf_token'=>'errado']));
verificar((int)$pdo->query("SELECT ativo FROM contatos_clientes WHERE id=$cid")->fetchColumn()===1,'CSRF de status relacionado');
requestTeste($salvarRelacionado); verificar(str_contains(flashTeste(),'Requisição inválida'),'GET não salva relacionado');
requestTeste($statusRelacionado); verificar(str_contains(flashTeste(),'Requisição inválida'),'GET não muda status relacionado');

// Associação adulterada mesmo entre dois clientes da mesma empresa.
$pdo->exec("INSERT INTO clientes(empresa_id,subcategoria_id,data_cadastro,nome_fantasia,razao_social,cnpj) VALUES(901,901,'2026-01-01','Outro cliente','Outro cliente','45.723.174/0001-10')");
$outro=(int)$pdo->lastInsertId();
verificar(requestTeste('/clientes/relacionado-form.php?tipo=contato&cliente_id='.$outro.'&id='.$cid)['status']===404,'Formulário recusa filho de outro cliente');
requestTeste($salvarRelacionado,array_replace($editarContato,['cliente_id'=>(string)$outro,'nome'=>'Adulterado']));
requestTeste($statusRelacionado,array_replace($statusContato,['cliente_id'=>(string)$outro]));
verificar($pdo->query("SELECT nome FROM contatos_clientes WHERE id=$cid")->fetchColumn()==='Pessoa Editada'
    && (int)$pdo->query("SELECT ativo FROM contatos_clientes WHERE id=$cid")->fetchColumn()===1,'Salvar/status recusam filho de outro cliente');
sessaoTeste(902,$todas);
verificar(requestTeste($destino)['status']===404,'Detalhes de outra empresa bloqueados');
verificar(requestTeste('/clientes/relacionado-form.php?tipo=endereco&cliente_id='.$id)['status']===404,'Formulário relacionado de outra empresa bloqueado');
requestTeste($salvarRelacionado,array_replace($editarContato,['nome'=>'Outra empresa']));
requestTeste($statusRelacionado,$statusContato);
verificar($pdo->query("SELECT nome FROM contatos_clientes WHERE id=$cid")->fetchColumn()==='Pessoa Editada'
    && (int)$pdo->query("SELECT ativo FROM contatos_clientes WHERE id=$cid")->fetchColumn()===1,'Escrita de relacionado de outra empresa bloqueada');
sessaoTeste(901,['CLIENTES_VISUALIZAR']);
verificar(requestTeste($destino)['status']===200 && !str_contains(requestTeste($destino)['body'],'+ Novo endereço'),'Detalhes somente leitura');
verificar(requestTeste('/clientes/relacionado-form.php?tipo=contato&cliente_id='.$id)['status']===403,'Permissão de formulário relacionado');
verificar(requestTeste($salvarRelacionado,$editarContato)['status']===403,'Permissão de salvar relacionado');
verificar(requestTeste($statusRelacionado,$statusContato)['status']===403,'Permissão de status relacionado');
sessaoTeste(901,[]); verificar(requestTeste($destino)['status']===403,'Permissão de detalhes');
sessaoTeste(901,$todas);
verificar(requestTeste('/clientes/detalhes.php?id[]=1')['status']===400,'Detalhes com ID array');
verificar(requestTeste('/clientes/relacionado-form.php?tipo=contato&cliente_id='.$id.'&id=1abc')['status']===400,'Relacionado com ID malformado');
verificar(requestTeste('/clientes/relacionado-form.php?tipo=SQL&cliente_id='.$id)['status']===400,'Tipo fora da lista rejeitado');

// Falha de auditoria deve desfazer a troca do principal, além da inserção.
session_id($sessionId); session_start(); $_SESSION['usuario_id']=999999; session_write_close();
$antes=(int)$pdo->query('SELECT COUNT(*) FROM enderecos_clientes')->fetchColumn();
requestTeste($salvarRelacionado,array_replace($segundo,['logradouro'=>'Rollback principal']));
verificar((int)$pdo->query('SELECT COUNT(*) FROM enderecos_clientes')->fetchColumn()===$antes
    && (int)$pdo->query("SELECT principal FROM enderecos_clientes WHERE id=$eid1")->fetchColumn()===1,'Rollback preserva principal anterior');
requestTeste($statusRelacionado,$statusContato);
verificar((int)$pdo->query("SELECT ativo FROM contatos_clientes WHERE id=$cid")->fetchColumn()===1,'Rollback de status relacionado com falha de auditoria');
sessaoTeste(901,$todas);
if(getenv('SGV9_TEST_SCHEMA') !== 'novo') {
    sessaoTeste(901,array_merge($todas,['LOCALIDADES_GERENCIAR']));
    requestTeste('/localidades/alterar-status-bairro.php',['csrf_token'=>'token-teste-clientes','id'=>'903','novo_status'=>'0']);
    verificar((int)$pdo->query('SELECT ativo FROM bairros WHERE id=903')->fetchColumn()===1
        && str_contains(flashTeste(),'endereços ativos'),'Localidades impede inativar bairro com endereço ativo');
}
sessaoTeste(901,$todas);
if (getenv('SGV9_CAPTURE_UI') === '1') {
    file_put_contents('/tmp/sgv9-qa-clientes-enderecos.html',requestTeste($destino.'&status=todos')['body']);
    file_put_contents('/tmp/sgv9-qa-clientes-contatos.html',requestTeste($destino.'&aba=contatos&status=todos')['body']);
    file_put_contents('/tmp/sgv9-qa-clientes-form.html',requestTeste('/clientes/relacionado-form.php?tipo=endereco&cliente_id='.$id.'&id='.$eid1)['body']);
}
