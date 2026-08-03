/*==============================================================*/
/* SGV9 - MIGRATION 005                                        */
/* Representadas editáveis e inativáveis                       */
/*==============================================================*/

START TRANSACTION;

ALTER TABLE representadas
    ADD COLUMN ativo BOOLEAN NOT NULL DEFAULT TRUE AFTER email,
    ADD COLUMN criado_em TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        AFTER ativo,
    ADD COLUMN atualizado_em TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
        AFTER criado_em;

COMMIT;
