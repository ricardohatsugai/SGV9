<?php
declare(strict_types=1);
require_once __DIR__.'/../auth.php';
require_once __DIR__.'/../includes/permissions.php';
require_once __DIR__.'/../includes/flash.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/session.php';
exigirPermissao('LOCALIDADES_GERENCIAR');
if($_SERVER['REQUEST_METHOD']!=='POST'||!validateCsrfToken($_POST['csrf_token']??null)){flash('error','Requisição inválida.');header('Location: /localidades/index.php');exit;}
$pdo=getDatabaseConnection();

$nome=mb_strtoupper(trim((string)($_POST['nome']??''))); $estadoId=(int)($_POST['estado_id']??0);
if($nome===''||!$estadoId){flash('error','Informe estado e cidade.');header('Location: /localidades/index.php?aba=cidades');exit;}
try{$s=$pdo->prepare('INSERT INTO cidades(nome,estado_id) VALUES(:nome,:estado_id)');$s->execute(['nome'=>$nome,'estado_id'=>$estadoId]);flash('success','Cidade cadastrada.');}
catch(PDOException $e){flash('error',$e->getCode()==='23000'?'Cidade já cadastrada neste estado.':'Falha ao cadastrar cidade.');}
header('Location: /localidades/index.php?aba=cidades');exit;
