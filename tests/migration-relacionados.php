<?php
declare(strict_types=1);
require '/var/www/html/config/database.php';
if (!preg_match('/^sgv9_clientes_test_[0-9]+$/D',getenv('DB_DATABASE') ?: '')) {
    throw new RuntimeException('Este teste exige banco isolado.');
}
$p=getDatabaseConnection();
$modo=$argv[1] ?? '';
if ($modo === 'seed') {
    $p->exec("INSERT INTO estados(id,nome,sigla) VALUES(810,'Histórico de endereço','XH')");
    $p->exec("INSERT INTO cidades(id,nome,estado_id) VALUES(810,'Histórica',810)");
    $p->exec("INSERT INTO bairros(id,nome,cidade_id) VALUES(810,'Histórico',810)");
    $tipo=$p->query("SELECT id FROM tipos_endereco WHERE tipo='ENTREGA'")->fetchColumn();
    $departamento=$p->query("SELECT id FROM departamentos_clientes WHERE nome='COMPRAS'")->fetchColumn();
    $p->prepare('INSERT INTO enderecos_clientes(id,cliente_id,tipo_endereco_id,logradouro,bairro_id,cep,principal,observacao) VALUES(801,801,?,\'Rua histórica\',810,\'12345-678\',1,\'Preservar endereço\')')->execute([$tipo]);
    $p->prepare('INSERT INTO contatos_clientes(id,cliente_id,departamento_id,nome,email,observacao) VALUES(801,801,?,\'Pessoa histórica\',\'historico@example.test\',\'Preservar contato\')')->execute([$departamento]);
    // Simula a tabela antiga sem controle de status nem principal único.
    $p->exec('ALTER TABLE enderecos_clientes ADD INDEX idx_enderecos_cliente_test(cliente_id)');
    $p->exec('ALTER TABLE enderecos_clientes DROP INDEX uq_enderecos_cliente_principal, DROP COLUMN principal_ativo');
    foreach(['enderecos_clientes','contatos_clientes'] as $tabela) {
        $p->exec("ALTER TABLE $tabela DROP COLUMN ativo,DROP COLUMN criado_em,DROP COLUMN atualizado_em");
    }
    $p->prepare('INSERT INTO enderecos_clientes(id,cliente_id,tipo_endereco_id,logradouro,bairro_id,cep,principal) VALUES(802,801,?,\'Outro principal\',810,\'12345-678\',1)')->execute([$tipo]);
} elseif ($modo === 'resolver-conflito') {
    if ((int)$p->query('SELECT COUNT(*) FROM enderecos_clientes WHERE cliente_id=801 AND principal=1')->fetchColumn() !== 2) {
        throw new RuntimeException('Migration resolveu conflito alterando registros históricos.');
    }
    $p->exec('UPDATE enderecos_clientes SET principal=0 WHERE id=802 AND cliente_id=801');
    echo "OK: Migration 007 recusou vários principais sem apagar ou escolher registros.\n";
} elseif ($modo === 'verify') {
    $endereco=$p->query('SELECT * FROM enderecos_clientes WHERE id=801')->fetch();
    $contato=$p->query('SELECT * FROM contatos_clientes WHERE id=801')->fetch();
    if (!$endereco || !$contato || $endereco['logradouro'] !== 'Rua histórica'
        || $contato['nome'] !== 'Pessoa histórica' || $contato['observacao'] !== 'Preservar contato'
        || (int)$endereco['cliente_id'] !== 801 || (int)$endereco['bairro_id'] !== 810
        || (int)$endereco['principal'] !== 1 || (int)$endereco['ativo'] !== 1
        || !$endereco['criado_em'] || !$contato['atualizado_em']) {
        throw new RuntimeException('Migration 007 não preservou dados ou campos de controle.');
    }
    if ((int)$p->query('SELECT COUNT(*) FROM tipos_endereco')->fetchColumn() !== 3
        || (int)$p->query('SELECT COUNT(*) FROM departamentos_clientes')->fetchColumn() !== 4) {
        throw new RuntimeException('Migration 007 duplicou dados de referência.');
    }
    echo "OK: Migration 007 preserva dados, IDs e FKs e pode ser reaplicada sem duplicar referências.\n";
} else { throw new RuntimeException('Modo desconhecido.'); }
