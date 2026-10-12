<?php
declare(strict_types=1);
require '/var/www/html/config/database.php';
if (!preg_match('/^sgv9_clientes_test_[0-9]+$/D', getenv('DB_DATABASE') ?: '')) {
    throw new RuntimeException('Este teste exige banco isolado.');
}
$p = getDatabaseConnection();
$modo = $argv[1] ?? '';
if ($modo === 'preparar-legado') {
    // Reconstrói os nomes observados no legado em uma cópia estrutural vazia.
    if ((int)$p->query('SELECT COUNT(*) FROM clientes')->fetchColumn() !== 0) {
        throw new RuntimeException('A reconstrução de schema exige cópia vazia.');
    }
    if ($p->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='categorias_clientes'")->fetchColumn()) {
        $p->exec('RENAME TABLE categorias_clientes TO categorias');
    }
    if ($p->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subcategorias_clientes'")->fetchColumn()) {
        $p->exec('RENAME TABLE subcategorias_clientes TO subcategorias');
    }
    foreach (['data_cadastro'=>'datacad','nome_fantasia'=>'nomefantasia','razao_social'=>'razaosocial',
        'inscricao_estadual'=>'inscr_est','inscricao_municipal'=>'inscr_mun',
        'subcategoria_id'=>'subcategorias_id','limite_credito'=>'credito','observacao'=>'obs'] as $novo=>$antigo) {
        $stmt=$p->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='clientes' AND COLUMN_NAME=:coluna");
        $stmt->execute(['coluna'=>$novo]);
        if ($stmt->fetchColumn()) {
            $p->exec("ALTER TABLE clientes RENAME COLUMN `$novo` TO `$antigo`");
        }
    }
    foreach (['situacao_cadastral','criado_em','atualizado_em'] as $coluna) {
        $stmt=$p->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='clientes' AND COLUMN_NAME=:coluna");
        $stmt->execute(['coluna'=>$coluna]);
        if ($stmt->fetchColumn()) {
            $p->exec("ALTER TABLE clientes DROP COLUMN `$coluna`");
        }
    }
    $p->exec('ALTER TABLE clientes MODIFY inscr_est VARCHAR(20) NOT NULL, MODIFY email VARCHAR(100) NOT NULL');
} elseif ($modo === 'seed') {
    $p->exec("INSERT INTO empresas (id,razao_social,nome_fantasia,cnpj) VALUES (801,'Histórico','Histórico','33.333.333/0001-33')");
    $legado = (bool)$p->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='clientes' AND COLUMN_NAME='datacad'")->fetchColumn();
    $categoria = $legado ? 'categorias' : 'categorias_clientes';
    $subcategoria = $legado ? 'subcategorias' : 'subcategorias_clientes';
    $p->exec("INSERT INTO $categoria (id,nome) VALUES (801,'Histórico')");
    $p->exec("INSERT INTO $subcategoria (id,nome,categoria_id) VALUES (801,'Histórico',801)");
    $colunas = $legado
        ? 'subcategorias_id,datacad,nomefantasia,razaosocial,inscr_est,credito,obs'
        : 'subcategoria_id,data_cadastro,nome_fantasia,razao_social,inscricao_estadual,limite_credito,observacao';
    $p->exec("INSERT INTO clientes (id,empresa_id,cnpj,email,$colunas)
        VALUES (801,801,'11222333000181','historico@example.test',801,'2020-02-29','Fantasia antiga','Razão antiga','ISENTO',999999999999999.1234,'Observação histórica')");
    $p->exec('CREATE TABLE teste_cliente_dependente (cliente_id INT '.($legado ? '' : 'UNSIGNED ').'NOT NULL,
        FOREIGN KEY (cliente_id) REFERENCES clientes(id)) ENGINE=InnoDB');
    $p->exec('INSERT INTO teste_cliente_dependente VALUES (801)');
} elseif ($modo === 'duplicate') {
    $colunas = $p->query('SELECT * FROM clientes WHERE id=801')->fetch();
    unset($colunas['id']);
    $colunas['id'] = 802;
    $colunas['cnpj'] = '11.222.333/0001-81';
    $sql = 'INSERT INTO clientes (`'.implode('`,`',array_keys($colunas)).'`) VALUES ('.implode(',',array_fill(0,count($colunas),'?')).')';
    $p->prepare($sql)->execute(array_values($colunas));
} elseif ($modo === 'remove-duplicate') {
    if ($p->query('SELECT cnpj FROM clientes WHERE id=801')->fetchColumn() !== '11222333000181') {
        throw new RuntimeException('Migration alterou dados apesar de conflito de CNPJ.');
    }
    $p->exec('DELETE FROM clientes WHERE id=802 AND empresa_id=801');
    echo "OK: Migration recusou CNPJs equivalentes antes de alterar dados.\n";
} elseif ($modo === 'verify') {
    $c = $p->query('SELECT * FROM clientes WHERE id=801')->fetch();
    foreach ([
        'empresa_id'=>'801','subcategoria_id'=>'801','data_cadastro'=>'2020-02-29',
        'nome_fantasia'=>'Fantasia antiga','razao_social'=>'Razão antiga','cnpj'=>'11.222.333/0001-81',
        'inscricao_estadual'=>'ISENTO','limite_credito'=>'999999999999999.1234','observacao'=>'Observação histórica',
    ] as $coluna=>$esperado) {
        if ((string)$c[$coluna] !== $esperado) {
            throw new RuntimeException('Migration alterou dado histórico: '.$coluna);
        }
    }
    if (!$c['criado_em'] || !$c['atualizado_em']
        || (int)$p->query('SELECT cliente_id FROM teste_cliente_dependente')->fetchColumn()!==801
        || (int)$p->query('SELECT categoria_id FROM subcategorias_clientes WHERE id=801')->fetchColumn()!==801) {
        throw new RuntimeException('Migration perdeu relacionamento ou campos de controle.');
    }
    try {
        $p->exec("INSERT INTO clientes (empresa_id,subcategoria_id,data_cadastro,nome_fantasia,razao_social,cnpj)
            VALUES (801,801,'2020-02-29','Duplicado','Duplicado','11.222.333/0001-81')");
        throw new RuntimeException('Índice UNIQUE não protege duplicidade.');
    } catch (PDOException $erro) {
        if (($erro->errorInfo[1] ?? null)!==1062) { throw $erro; }
    }
    echo "OK: Migration preservou IDs, valores, precisão decimal, FKs e unicidade.\n";
} else {
    throw new RuntimeException('Modo desconhecido.');
}
