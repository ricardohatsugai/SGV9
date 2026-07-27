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

$nome=mb_strtoupper(trim((string)($_POST['nome']??''))); $cidadeId=(int)($_POST['cidade_id']??0); $retorno=(string)($_POST['retorno']??''); $rid=(int)($_POST['representada_id']??0);
if($nome===''||!$cidadeId){flash('error','Informe cidade e bairro.');header('Location: /localidades/index.php?aba=bairros');exit;}
try{$s=$pdo->prepare('INSERT INTO bairros(nome,cidade_id) VALUES(:nome,:cidade_id)');$s->execute(['nome'=>$nome,'cidade_id'=>$cidadeId]);$bid=(int)$pdo->lastInsertId();flash('success','Bairro cadastrado.');
if($retorno==='representada'){$url='/representadas/form.php?bairro_id='.$bid.($rid?'&id='.$rid:'');header('Location: '.$url);exit;}}
catch(PDOException $e){flash('error',$e->getCode()==='23000'?'Bairro já cadastrado nesta cidade.':'Falha ao cadastrar bairro.');}
header('Location: /localidades/index.php?aba=bairros');exit;
