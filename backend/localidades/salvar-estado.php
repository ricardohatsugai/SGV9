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

$nome=mb_strtoupper(trim((string)($_POST['nome']??''))); $sigla=mb_strtoupper(trim((string)($_POST['sigla']??'')));
if($nome===''||!preg_match('/^[A-Z]{2}$/',$sigla)){flash('error','Informe nome e sigla válida.');header('Location: /localidades/index.php?aba=estados');exit;}
try{$s=$pdo->prepare('INSERT INTO estados(nome,sigla) VALUES(:nome,:sigla)');$s->execute(compact('nome','sigla'));flash('success','Estado cadastrado.');}
catch(PDOException $e){flash('error',$e->getCode()==='23000'?'Estado ou sigla já cadastrado.':'Falha ao cadastrar estado.');}
header('Location: /localidades/index.php?aba=estados');exit;
