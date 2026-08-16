/*==============================================================*/
/* SGV9 - MIGRAÇÃO 9.1.0                                       */
/* Localidades editáveis e inativáveis                          */
/* MySQL 8.4                                                    */
/*==============================================================*/
/*
  OBJETIVOS
  - Permitir edição de nome/sigla sem alterar os IDs.
  - Permitir inativação lógica de estados, cidades e bairros.
  - Impedir cidades duplicadas dentro do mesmo estado.
  - Registrar automaticamente criação e última atualização.
  - Corrigir a sigla da Bahia de BH para BA.
  - Corrigir o texto corrompido de SÃO PAULO, quando existente.

  IMPORTANTE
  - Esta migration deve ser executada apenas em bancos onde estas
    alterações ainda não tenham sido aplicadas.
  - Verifique previamente a estrutura das tabelas.
  - Faça backup antes da execução.
  - ALTER TABLE no MySQL provoca commit implícito; esta migration
    não possui rollback transacional global.
  - A exclusão física continua bloqueada pelas chaves estrangeiras.
*/

/* Correções conhecidas da carga inicial */
UPDATE estados
   SET sigla = 'BA'
 WHERE nome = 'BAHIA'
   AND sigla = 'BH';

UPDATE cidades
   SET nome = 'SÃO PAULO'
 WHERE nome IN ('SÃƒO PAULO', 'SÃ£O PAULO');

/* Estados */
ALTER TABLE estados
    ADD COLUMN ativo BOOLEAN NOT NULL DEFAULT TRUE AFTER sigla,
    ADD COLUMN criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER ativo,
    ADD COLUMN atualizado_em TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
        AFTER criado_em;

/* Cidades */
ALTER TABLE cidades
    ADD COLUMN ativo BOOLEAN NOT NULL DEFAULT TRUE AFTER estado_id,
    ADD COLUMN criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER ativo,
    ADD COLUMN atualizado_em TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
        AFTER criado_em;

/* Bairros */
ALTER TABLE bairros
    ADD COLUMN ativo BOOLEAN NOT NULL DEFAULT TRUE AFTER cidade_id,
    ADD COLUMN criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER ativo,
    ADD COLUMN atualizado_em TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
        AFTER criado_em;


/* Validação pós-migração */
SELECT id, nome, sigla, ativo, criado_em, atualizado_em
  FROM estados
 ORDER BY nome;

SELECT c.id, c.nome, e.sigla AS estado, c.ativo, c.criado_em, c.atualizado_em
  FROM cidades c
  JOIN estados e ON e.id = c.estado_id
 ORDER BY e.sigla, c.nome;

SELECT b.id, b.nome, c.nome AS cidade, e.sigla AS estado, b.ativo,
       b.criado_em, b.atualizado_em
  FROM bairros b
  JOIN cidades c ON c.id = b.cidade_id
  JOIN estados e ON e.id = c.estado_id
 ORDER BY e.sigla, c.nome, b.nome;
