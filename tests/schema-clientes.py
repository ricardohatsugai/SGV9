"""Extrai o DDL versionado das tabelas envolvidas em Clientes para banco isolado."""
import re
from pathlib import Path

ddl = (Path(__file__).resolve().parents[1] / 'init-scripts/01-ddl.sql').read_text(encoding='utf-8')
tables = ('empresas', 'perfis', 'permissoes', 'perfil_permissoes', 'usuarios',
          'categorias_clientes', 'subcategorias_clientes', 'clientes', 'logs_usuarios',
          'estados', 'cidades', 'bairros', 'tipos_endereco', 'enderecos_clientes',
          'departamentos_clientes', 'contatos_clientes')
for table in tables:
    match = re.search(rf'CREATE TABLE {table} \(.*?\) ENGINE=InnoDB;', ddl, re.S)
    if not match:
        raise RuntimeError(f'Tabela ausente no DDL: {table}')
    print(match.group(0))
