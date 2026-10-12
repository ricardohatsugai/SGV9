/* SGV9 - Cadastro de Clientes.
   Execute no banco selecionado, com a aplicação em manutenção e backup prévio.
   DDL do MySQL faz commit implícito: esta migration não é transacional.
   Renomeia apenas estruturas legadas de clientes; preserva IDs, dados e FKs.
   Pode ser reaplicada. Não executa exclusões nem recria tabelas. */

DELIMITER $$
DROP PROCEDURE IF EXISTS sgv9_migrar_clientes_006$$
CREATE PROCEDURE sgv9_migrar_clientes_006()
BEGIN
    DECLARE terminou BOOLEAN DEFAULT FALSE;
    DECLARE antigo VARCHAR(64);
    DECLARE novo VARCHAR(64);
    DECLARE existe_antigo INT;
    DECLARE existe_novo INT;
    DECLARE colunas CURSOR FOR
        SELECT 'datacad', 'data_cadastro' UNION ALL
        SELECT 'nomefantasia', 'nome_fantasia' UNION ALL
        SELECT 'razaosocial', 'razao_social' UNION ALL
        SELECT 'inscr_est', 'inscricao_estadual' UNION ALL
        SELECT 'inscr_mun', 'inscricao_municipal' UNION ALL
        SELECT 'subcategorias_id', 'subcategoria_id' UNION ALL
        SELECT 'credito', 'limite_credito' UNION ALL
        SELECT 'obs', 'observacao';
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET terminou = TRUE;

    /* Antes de qualquer DDL, recusar schemas ambíguos e dependências inesperadas. */
    IF EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='categorias')
       AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='categorias_clientes') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Existem categorias legadas e atuais; reconcilie antes de migrar.';
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subcategorias')
       AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subcategorias_clientes') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Existem subcategorias legadas e atuais; reconcilie antes de migrar.';
    END IF;
    IF EXISTS (
        SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
        WHERE REFERENCED_TABLE_SCHEMA=DATABASE()
          AND ((REFERENCED_TABLE_NAME='categorias' AND TABLE_NAME<>'subcategorias')
            OR (REFERENCED_TABLE_NAME='subcategorias' AND TABLE_NAME<>'clientes'))
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Categorias legadas possuem outras dependencias; revise antes de migrar.';
    END IF;

    OPEN colunas;
    preflight: LOOP
        FETCH colunas INTO antigo, novo;
        IF terminou THEN LEAVE preflight; END IF;
        SELECT COUNT(*) INTO existe_antigo FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='clientes' AND COLUMN_NAME=antigo;
        SELECT COUNT(*) INTO existe_novo FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='clientes' AND COLUMN_NAME=novo;
        IF existe_antigo + existe_novo <> 1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Colunas de clientes ausentes ou ambiguas; revise o schema.';
        END IF;
    END LOOP;
    CLOSE colunas;

    IF EXISTS (SELECT 1 FROM clientes WHERE cnpj NOT REGEXP '^([0-9]{14}|[0-9]{2}\\.[0-9]{3}\\.[0-9]{3}/[0-9]{4}-[0-9]{2})$') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Existem CNPJs fora do formato esperado; revise antes de migrar.';
    END IF;
    IF EXISTS (
        SELECT empresa_id FROM clientes
        GROUP BY empresa_id, REPLACE(REPLACE(REPLACE(cnpj,'.',''),'/',''),'-','')
        HAVING COUNT(*)>1
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Existem CNPJs equivalentes duplicados; reconcilie antes de migrar.';
    END IF;

    IF EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='categorias') THEN
        RENAME TABLE categorias TO categorias_clientes;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subcategorias') THEN
        RENAME TABLE subcategorias TO subcategorias_clientes;
    END IF;

    SET terminou = FALSE;
    OPEN colunas;
    renomear: LOOP
        FETCH colunas INTO antigo, novo;
        IF terminou THEN LEAVE renomear; END IF;
        IF EXISTS (SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='clientes' AND COLUMN_NAME=antigo) THEN
            SET @sgv9_ddl = CONCAT('ALTER TABLE clientes RENAME COLUMN `', antigo, '` TO `', novo, '`');
            PREPARE sgv9_stmt FROM @sgv9_ddl;
            EXECUTE sgv9_stmt;
            DEALLOCATE PREPARE sgv9_stmt;
        END IF;
    END LOOP;
    CLOSE colunas;

    ALTER TABLE clientes
        MODIFY inscricao_estadual VARCHAR(20) NULL,
        MODIFY email VARCHAR(150) NULL;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='clientes' AND COLUMN_NAME='situacao_cadastral') THEN
        ALTER TABLE clientes ADD COLUMN situacao_cadastral VARCHAR(50) NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='clientes' AND COLUMN_NAME='criado_em') THEN
        ALTER TABLE clientes ADD COLUMN criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='clientes' AND COLUMN_NAME='atualizado_em') THEN
        ALTER TABLE clientes ADD COLUMN atualizado_em TIMESTAMP NOT NULL
            DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
    END IF;

    /* Padronização para a UNIQUE (empresa_id, cnpj) também proteger a concorrência. */
    UPDATE clientes
    SET cnpj=CONCAT(SUBSTRING(cnpj,1,2),'.',SUBSTRING(cnpj,3,3),'.',SUBSTRING(cnpj,6,3),'/',SUBSTRING(cnpj,9,4),'-',SUBSTRING(cnpj,13,2))
    WHERE cnpj REGEXP '^[0-9]{14}$';
END$$
DELIMITER ;

CALL sgv9_migrar_clientes_006();
DROP PROCEDURE sgv9_migrar_clientes_006;
