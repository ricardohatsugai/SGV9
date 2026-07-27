<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/layout.php'; require_once __DIR__.'/../config/database.php';
exigirPermissao('LOCALIDADES_GERENCIAR'); $pdo=getDatabaseConnection();
$cidades=$pdo->query('SELECT c.id,c.nome,e.sigla FROM cidades c JOIN estados e ON e.id=c.estado_id ORDER BY e.sigla,c.nome')->fetchAll();
$retorno=$_GET['retorno']??''; $rid=(int)($_GET['representada_id']??0);
renderHeader('Cadastrar bairro','Cadastre rapidamente e retorne à representada.');
?>
<form action="/localidades/salvar-bairro.php" method="post" class="panel form-panel-admin narrow">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="retorno" value="<?= e($retorno) ?>"><input type="hidden" name="representada_id" value="<?= $rid ?>">
<div class="form-grid"><label class="field span-2">Cidade<select name="cidade_id" required><option value="">Selecione</option><?php foreach($cidades as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['nome'].'/'.$c['sigla']) ?></option><?php endforeach; ?></select><a class="inline-link" href="/localidades/index.php?aba=cidades">Cidade não cadastrada? Cadastre no módulo Localidades.</a></label><label class="field span-2">Bairro<input name="nome" required maxlength="200"></label></div>
<div class="form-actions"><a class="button secondary" href="<?= $rid?'/representadas/form.php?id='.$rid:'/representadas/form.php' ?>">Cancelar</a><button class="button primary">Salvar e usar</button></div></form><?php renderFooter(); ?>
