<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/layout.php';
require_once __DIR__.'/../config/database.php';
exigirPermissao('LOCALIDADES_VISUALIZAR');
$pdo=getDatabaseConnection(); $aba=$_GET['aba']??'bairros';
$estados=$pdo->query('SELECT id,nome,sigla FROM estados ORDER BY nome')->fetchAll();
$cidades=$pdo->query('SELECT c.id,c.nome,e.sigla,e.nome estado FROM cidades c JOIN estados e ON e.id=c.estado_id ORDER BY e.sigla,c.nome')->fetchAll();
$bairros=$pdo->query('SELECT b.id,b.nome,c.nome cidade,e.sigla FROM bairros b JOIN cidades c ON c.id=b.cidade_id JOIN estados e ON e.id=c.estado_id ORDER BY e.sigla,c.nome,b.nome')->fetchAll();
renderHeader('Localidades','Cadastre estados, cidades e bairros usados no sistema.');
?>
<div class="tabs">
<a href="?aba=bairros" class="<?= $aba==='bairros'?'active':'' ?>">Bairros</a>
<a href="?aba=cidades" class="<?= $aba==='cidades'?'active':'' ?>">Cidades</a>
<a href="?aba=estados" class="<?= $aba==='estados'?'active':'' ?>">Estados</a>
</div>
<?php if($aba==='estados'): ?>
<div class="split-grid">
<?php if(usuarioTemPermissao('LOCALIDADES_GERENCIAR')): ?><form action="/localidades/salvar-estado.php" method="post" class="panel compact-form"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><h2>Novo estado</h2><div class="form-grid"><label class="field span-2">Nome<input name="nome" required maxlength="100"></label><label class="field">Sigla<input name="sigla" required maxlength="2"></label></div><div class="form-actions"><button class="button primary">Salvar estado</button></div></form><?php endif; ?>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Estado</th><th>Sigla</th></tr></thead><tbody><?php foreach($estados as $e): ?><tr><td><?= e($e['nome']) ?></td><td><?= e($e['sigla']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
</div>
<?php elseif($aba==='cidades'): ?>
<div class="split-grid">
<?php if(usuarioTemPermissao('LOCALIDADES_GERENCIAR')): ?><form action="/localidades/salvar-cidade.php" method="post" class="panel compact-form"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><h2>Nova cidade</h2><div class="form-grid"><label class="field span-2">Estado<select name="estado_id" required><option value="">Selecione</option><?php foreach($estados as $e): ?><option value="<?= (int)$e['id'] ?>"><?= e($e['nome'].'/'.$e['sigla']) ?></option><?php endforeach; ?></select></label><label class="field span-2">Cidade<input name="nome" required maxlength="150"></label></div><div class="form-actions"><button class="button primary">Salvar cidade</button></div></form><?php endif; ?>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Cidade</th><th>Estado</th></tr></thead><tbody><?php foreach($cidades as $c): ?><tr><td><?= e($c['nome']) ?></td><td><?= e($c['estado'].'/'.$c['sigla']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
</div>
<?php else: ?>
<div class="split-grid">
<?php if(usuarioTemPermissao('LOCALIDADES_GERENCIAR')): ?><form action="/localidades/salvar-bairro.php" method="post" class="panel compact-form"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><h2>Novo bairro</h2><div class="form-grid"><label class="field span-2">Cidade<select name="cidade_id" required><option value="">Selecione</option><?php foreach($cidades as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['nome'].'/'.$c['sigla']) ?></option><?php endforeach; ?></select></label><label class="field span-2">Bairro<input name="nome" required maxlength="200"></label></div><div class="form-actions"><button class="button primary">Salvar bairro</button></div></form><?php endif; ?>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Bairro</th><th>Cidade</th><th>UF</th></tr></thead><tbody><?php foreach($bairros as $b): ?><tr><td><?= e($b['nome']) ?></td><td><?= e($b['cidade']) ?></td><td><?= e($b['sigla']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
</div>
<?php endif; renderFooter(); ?>
