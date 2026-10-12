/* SGV9 - Endereços e contatos de Clientes.
   Aplicar após a migration 006, em manutenção e com backup.
   DDL do MySQL faz commit implícito. Não exclui registros.
   Os tipos das FKs são obtidos dos pais para suportar banco legado e DDL atual. */
DELIMITER $$
DROP PROCEDURE IF EXISTS sgv9_clientes_relacionados_007$$
CREATE PROCEDURE sgv9_clientes_relacionados_007()
BEGIN
    DECLARE tipo_cliente VARCHAR(64);
    DECLARE tipo_bairro VARCHAR(64);
    DECLARE tipo_endereco VARCHAR(64);
    DECLARE tipo_departamento VARCHAR(64);
    DECLARE tabela VARCHAR(64);
    DECLARE terminou BOOLEAN DEFAULT FALSE;
    DECLARE tabelas CURSOR FOR SELECT 'enderecos_clientes' UNION ALL SELECT 'contatos_clientes';
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET terminou=TRUE;

    SELECT COLUMN_TYPE INTO tipo_cliente FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='clientes' AND COLUMN_NAME='id';
    SELECT COLUMN_TYPE INTO tipo_bairro FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bairros' AND COLUMN_NAME='id';
    IF tipo_cliente IS NULL OR tipo_bairro IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Clientes e bairros devem existir antes da migration 007.';
    END IF;

    CREATE TABLE IF NOT EXISTS tipos_endereco (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tipo VARCHAR(50) NOT NULL,
        CONSTRAINT uq_tipos_endereco_tipo UNIQUE(tipo)
    ) ENGINE=InnoDB;
    CREATE TABLE IF NOT EXISTS departamentos_clientes (
        id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(50) NOT NULL,
        CONSTRAINT uq_departamentos_clientes_nome UNIQUE(nome)
    ) ENGINE=InnoDB;
    SELECT COLUMN_TYPE INTO tipo_endereco FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tipos_endereco' AND COLUMN_NAME='id';
    SELECT COLUMN_TYPE INTO tipo_departamento FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='departamentos_clientes' AND COLUMN_NAME='id';

    SET @sgv9_ddl=CONCAT('CREATE TABLE IF NOT EXISTS enderecos_clientes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        cliente_id ',tipo_cliente,' NOT NULL,
        tipo_endereco_id ',tipo_endereco,' NOT NULL,
        logradouro VARCHAR(200) NOT NULL, numero VARCHAR(20), complemento VARCHAR(200),
        bairro_id ',tipo_bairro,' NOT NULL, cep VARCHAR(9) NOT NULL,
        contato VARCHAR(100), telefone VARCHAR(20), celular VARCHAR(20), email VARCHAR(150),
        principal BOOLEAN NOT NULL DEFAULT FALSE, observacao TEXT,
        CONSTRAINT fk_enderecos_clientes_cliente FOREIGN KEY(cliente_id) REFERENCES clientes(id) ON UPDATE CASCADE ON DELETE RESTRICT,
        CONSTRAINT fk_enderecos_clientes_tipo FOREIGN KEY(tipo_endereco_id) REFERENCES tipos_endereco(id) ON UPDATE CASCADE ON DELETE RESTRICT,
        CONSTRAINT fk_enderecos_clientes_bairro FOREIGN KEY(bairro_id) REFERENCES bairros(id) ON UPDATE CASCADE ON DELETE RESTRICT
    ) ENGINE=InnoDB');
    PREPARE sgv9_stmt FROM @sgv9_ddl; EXECUTE sgv9_stmt; DEALLOCATE PREPARE sgv9_stmt;
    SET @sgv9_ddl=CONCAT('CREATE TABLE IF NOT EXISTS contatos_clientes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        cliente_id ',tipo_cliente,' NOT NULL,
        departamento_id ',tipo_departamento,' NOT NULL, nome VARCHAR(100) NOT NULL,
        telefone VARCHAR(20), celular VARCHAR(20), email VARCHAR(150), observacao TEXT,
        CONSTRAINT uq_contatos_clientes UNIQUE(cliente_id,departamento_id,nome),
        CONSTRAINT fk_contatos_clientes_cliente FOREIGN KEY(cliente_id) REFERENCES clientes(id) ON UPDATE CASCADE ON DELETE RESTRICT,
        CONSTRAINT fk_contatos_clientes_departamento FOREIGN KEY(departamento_id) REFERENCES departamentos_clientes(id) ON UPDATE CASCADE ON DELETE RESTRICT
    ) ENGINE=InnoDB');
    PREPARE sgv9_stmt FROM @sgv9_ddl; EXECUTE sgv9_stmt; DEALLOCATE PREPARE sgv9_stmt;

    SET terminou=FALSE;
    OPEN tabelas;
    adicionar: LOOP
        FETCH tabelas INTO tabela;
        IF terminou THEN LEAVE adicionar; END IF;
        IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=tabela AND COLUMN_NAME='ativo') THEN
            SET @sgv9_ddl=CONCAT('ALTER TABLE ',tabela,' ADD ativo BOOLEAN NOT NULL DEFAULT TRUE');
            PREPARE sgv9_stmt FROM @sgv9_ddl; EXECUTE sgv9_stmt; DEALLOCATE PREPARE sgv9_stmt;
        END IF;
        IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=tabela AND COLUMN_NAME='criado_em') THEN
            SET @sgv9_ddl=CONCAT('ALTER TABLE ',tabela,' ADD criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
            PREPARE sgv9_stmt FROM @sgv9_ddl; EXECUTE sgv9_stmt; DEALLOCATE PREPARE sgv9_stmt;
        END IF;
        IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=tabela AND COLUMN_NAME='atualizado_em') THEN
            SET @sgv9_ddl=CONCAT('ALTER TABLE ',tabela,' ADD atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
            PREPARE sgv9_stmt FROM @sgv9_ddl; EXECUTE sgv9_stmt; DEALLOCATE PREPARE sgv9_stmt;
        END IF;
    END LOOP;
    CLOSE tabelas;

    IF EXISTS (SELECT cliente_id FROM enderecos_clientes WHERE ativo=1 AND principal=1 GROUP BY cliente_id HAVING COUNT(*)>1) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Existem varios enderecos principais ativos; reconcilie sem excluir historico.';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='enderecos_clientes' AND COLUMN_NAME='principal_ativo') THEN
        ALTER TABLE enderecos_clientes ADD principal_ativo TINYINT
            GENERATED ALWAYS AS (CASE WHEN ativo=1 AND principal=1 THEN 1 ELSE NULL END) STORED;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='enderecos_clientes' AND INDEX_NAME='uq_enderecos_cliente_principal') THEN
        ALTER TABLE enderecos_clientes ADD CONSTRAINT uq_enderecos_cliente_principal UNIQUE(cliente_id,principal_ativo);
    END IF;

    INSERT INTO tipos_endereco(tipo)
        SELECT 'FATURAMENTO' WHERE NOT EXISTS (SELECT 1 FROM tipos_endereco WHERE tipo='FATURAMENTO');
    INSERT INTO tipos_endereco(tipo)
        SELECT 'ENTREGA' WHERE NOT EXISTS (SELECT 1 FROM tipos_endereco WHERE tipo='ENTREGA');
    INSERT INTO tipos_endereco(tipo)
        SELECT 'COBRANÇA' WHERE NOT EXISTS (SELECT 1 FROM tipos_endereco WHERE tipo='COBRANÇA');
    INSERT INTO departamentos_clientes(nome)
        SELECT 'FINANCEIRO' WHERE NOT EXISTS (SELECT 1 FROM departamentos_clientes WHERE nome='FINANCEIRO');
    INSERT INTO departamentos_clientes(nome)
        SELECT 'COMERCIAL' WHERE NOT EXISTS (SELECT 1 FROM departamentos_clientes WHERE nome='COMERCIAL');
    INSERT INTO departamentos_clientes(nome)
        SELECT 'COMPRAS' WHERE NOT EXISTS (SELECT 1 FROM departamentos_clientes WHERE nome='COMPRAS');
    INSERT INTO departamentos_clientes(nome)
        SELECT 'LOGÍSTICA' WHERE NOT EXISTS (SELECT 1 FROM departamentos_clientes WHERE nome='LOGÍSTICA');
END$$
DELIMITER ;
CALL sgv9_clientes_relacionados_007();
DROP PROCEDURE sgv9_clientes_relacionados_007;
