-- Habilita a primeira etapa de Clientes sem alterar tabelas comerciais.
-- Pode ser reexecutada; mantém permissões e associações já existentes.
START TRANSACTION;

INSERT INTO permissoes (codigo, descricao)
SELECT 'CLIENTES_VISUALIZAR', 'Visualizar clientes'
WHERE NOT EXISTS (SELECT 1 FROM permissoes WHERE codigo = 'CLIENTES_VISUALIZAR');

INSERT INTO permissoes (codigo, descricao)
SELECT 'CLIENTES_GERENCIAR', 'Criar, editar, ativar e inativar clientes'
WHERE NOT EXISTS (SELECT 1 FROM permissoes WHERE codigo = 'CLIENTES_GERENCIAR');

-- Mesma política de perfis dos init-scripts/02-dml.sql.
INSERT INTO perfil_permissoes (perfil_id, permissao_id)
SELECT p.id, pe.id
FROM perfis p
CROSS JOIN permissoes pe
WHERE p.nome IN ('ADMINISTRADOR', 'VENDEDOR', 'ASSISTENTE')
  AND pe.codigo IN ('CLIENTES_VISUALIZAR', 'CLIENTES_GERENCIAR')
  AND NOT EXISTS (
      SELECT 1 FROM perfil_permissoes pp
      WHERE pp.perfil_id = p.id AND pp.permissao_id = pe.id
  );

COMMIT;
