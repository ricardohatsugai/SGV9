USE sgv9;
START TRANSACTION;
INSERT IGNORE INTO permissoes(codigo,descricao) VALUES
('LOCALIDADES_VISUALIZAR','Visualizar estados, cidades e bairros'),
('LOCALIDADES_GERENCIAR','Cadastrar estados, cidades e bairros');
INSERT IGNORE INTO perfil_permissoes(perfil_id,permissao_id)
SELECT p.id,pe.id FROM perfis p JOIN permissoes pe ON pe.codigo IN('LOCALIDADES_VISUALIZAR','LOCALIDADES_GERENCIAR') WHERE p.nome IN('ADMINISTRADOR','ASSISTENTE');
INSERT IGNORE INTO perfil_permissoes(perfil_id,permissao_id)
SELECT p.id,pe.id FROM perfis p JOIN permissoes pe ON pe.codigo='LOCALIDADES_VISUALIZAR' WHERE p.nome IN('VENDEDOR','CONSULTA');
COMMIT;
