"""Testa Clientes em bancos exclusivos; não grava no banco de trabalho."""
import argparse
import json
import pathlib
import re
import subprocess
import tempfile
import uuid


def executar(args, entrada=None):
    resultado = subprocess.run(args, input=entrada, text=True, capture_output=True)
    if resultado.returncode:
        raise RuntimeError(resultado.stdout + resultado.stderr)
    return resultado.stdout


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--php-container', required=True)
    parser.add_argument('--db-container', required=True)
    args = parser.parse_args()
    raiz = pathlib.Path(__file__).resolve().parents[1]
    prefixo = 'sgv9_test_clientes_' + uuid.uuid4().hex[:12]
    pasta_container = '/tmp/' + prefixo
    exportar = '''<?php
require '/var/www/html/config/database.php';
$pdo = getDatabaseConnection();
$colunas = $pdo->query('SHOW COLUMNS FROM clientes')->fetchAll(PDO::FETCH_COLUMN);
$legado = in_array('datacad', $colunas, true);
$tabelas = ['empresas','perfis','permissoes','perfil_permissoes','usuarios',
    $legado ? 'categorias' : 'categorias_clientes',
    $legado ? 'subcategorias' : 'subcategorias_clientes','clientes','logs_usuarios'];
$ddl = [];
foreach ($tabelas as $tabela) {
    $ddl[] = $pdo->query('SHOW CREATE TABLE ' . $tabela)->fetch(PDO::FETCH_NUM)[1] . ';';
}
echo json_encode(['usuario' => $pdo->query('SELECT CURRENT_USER()')->fetchColumn(), 'ddl' => implode("\\n", $ddl)], JSON_THROW_ON_ERROR);
'''
    exportado = json.loads(executar(['docker', 'exec', '-i', args.php_container, 'php'], exportar))
    usuario, host = exportado['usuario'].rsplit('@', 1)
    if not re.fullmatch(r'[A-Za-z0-9_]+', usuario) or not re.fullmatch(r'[A-Za-z0-9_.%:-]+', host):
        raise RuntimeError('Conta de teste não reconhecida.')
    ddl_atual = (raiz / 'init-scripts/01-ddl.sql').read_text(encoding='utf-8')
    tabelas = ['empresas', 'perfis', 'permissoes', 'perfil_permissoes', 'usuarios',
               'categorias_clientes', 'subcategorias_clientes', 'clientes', 'logs_usuarios']
    blocos = []
    for tabela in tabelas:
        bloco = re.search(r'CREATE TABLE ' + tabela + r' \(.*?\) ENGINE=InnoDB;', ddl_atual, re.S)
        if not bloco:
            raise RuntimeError('Tabela ausente no DDL versionado: ' + tabela)
        blocos.append(bloco.group())
    mysql = ['docker', 'exec', '-i', args.db_container, 'sh', '-c',
             'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysql -u root --default-character-set=utf8mb4']
    criados = []
    executar(['docker', 'exec', args.php_container, 'mkdir', '-p', pasta_container])
    try:
        executar(['docker', 'cp', str(raiz / 'tests/clientes.php'), args.php_container + ':' + pasta_container + '/clientes.php'])
        executar(['docker', 'cp', str(raiz / 'database/migrations/006-clientes-permissoes.sql'), args.php_container + ':' + pasta_container + '/migration.sql'])
        with tempfile.TemporaryDirectory(prefix=prefixo) as temporario:
            for modelo, ddl in [('local', exportado['ddl']), ('versionado', '\n'.join(blocos))]:
                banco = prefixo + '_' + modelo
                if not re.fullmatch(r'sgv9_test_clientes_[0-9a-f]{12}_(local|versionado)', banco):
                    raise RuntimeError('Nome de banco de teste inseguro.')
                executar(mysql, f"CREATE DATABASE `{banco}` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;\n")
                criados.append(banco)
                executar(mysql, f"GRANT ALL ON `{banco}`.* TO '{usuario}'@'{host}';\n")
                caminho = pathlib.Path(temporario) / 'schema.sql'
                caminho.write_text(ddl, encoding='utf-8')
                executar(['docker', 'cp', str(caminho), args.php_container + ':' + pasta_container + '/schema.sql'])
                saida = executar(['docker', 'exec', '-e', 'DB_DATABASE=' + banco, args.php_container,
                                  'php', pasta_container + '/clientes.php'])
                print(modelo + ': ' + saida.strip())
    finally:
        # Somente bancos criados acima, com nome exclusivo e novamente validado.
        for banco in criados:
            if re.fullmatch(r'sgv9_test_clientes_[0-9a-f]{12}_(local|versionado)', banco):
                executar(mysql, f"REVOKE ALL PRIVILEGES ON `{banco}`.* FROM '{usuario}'@'{host}';\nDROP DATABASE `{banco}`;\n")
        # Apenas arquivos exclusivos desta execução, nunca a raiz /tmp.
        executar(['docker', 'exec', args.php_container, 'rm', '-r', '--', pasta_container])


if __name__ == '__main__':
    main()
