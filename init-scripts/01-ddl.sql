<<<<<<< HEAD
/*==============================================================*/
/* SGV9 - DDL V2                                                */
/* ERP enxuto para escritórios de representação comercial       */
/* Banco: MySQL 8.4+                                            */
/*==============================================================*/

CREATE DATABASE IF NOT EXISTS sgv9
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_0900_ai_ci;

USE sgv9;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

/*==============================================================*/
/* ORGANIZAÇÃO, USUÁRIOS E PERMISSÕES                            */
/*==============================================================*/

CREATE TABLE empresas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    razao_social VARCHAR(200) NOT NULL,
    nome_fantasia VARCHAR(200) NOT NULL,
    cnpj VARCHAR(18) NOT NULL,
    telefone VARCHAR(20),
    email VARCHAR(150),
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_empresas_cnpj UNIQUE (cnpj)
) ENGINE=InnoDB;

CREATE TABLE perfis (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    descricao VARCHAR(255),
    CONSTRAINT uq_perfis_nome UNIQUE (nome)
) ENGINE=InnoDB;

CREATE TABLE permissoes (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(100) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    CONSTRAINT uq_permissoes_codigo UNIQUE (codigo)
) ENGINE=InnoDB;

CREATE TABLE perfil_permissoes (
    perfil_id SMALLINT UNSIGNED NOT NULL,
    permissao_id SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (perfil_id, permissao_id),
    CONSTRAINT fk_perfil_permissoes_perfil
        FOREIGN KEY (perfil_id) REFERENCES perfis(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_perfil_permissoes_permissao
        FOREIGN KEY (permissao_id) REFERENCES permissoes(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    perfil_id SMALLINT UNSIGNED NOT NULL,
    nome VARCHAR(200) NOT NULL,
    email VARCHAR(200) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    ultimo_login_em DATETIME NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_usuarios_empresa_email UNIQUE (empresa_id, email),
    CONSTRAINT fk_usuarios_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_usuarios_perfil
        FOREIGN KEY (perfil_id) REFERENCES perfis(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

/*==============================================================*/
/* LOCALIZAÇÃO                                                  */
/*==============================================================*/

CREATE TABLE estados (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    sigla CHAR(2) NOT NULL,
    CONSTRAINT uq_estados_nome UNIQUE (nome),
    CONSTRAINT uq_estados_sigla UNIQUE (sigla)
) ENGINE=InnoDB;

CREATE TABLE cidades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    estado_id TINYINT UNSIGNED NOT NULL,
    CONSTRAINT uq_cidades_estado_nome UNIQUE (estado_id, nome),
    CONSTRAINT fk_cidades_estado
        FOREIGN KEY (estado_id) REFERENCES estados(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE bairros (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    cidade_id INT UNSIGNED NOT NULL,
    CONSTRAINT uq_bairros_cidade_nome UNIQUE (cidade_id, nome),
    CONSTRAINT fk_bairros_cidade
        FOREIGN KEY (cidade_id) REFERENCES cidades(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

/*==============================================================*/
/* CADASTROS COMERCIAIS                                         */
/*==============================================================*/

CREATE TABLE categorias_clientes (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    CONSTRAINT uq_categorias_clientes_nome UNIQUE (nome)
) ENGINE=InnoDB;

CREATE TABLE subcategorias_clientes (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id SMALLINT UNSIGNED NOT NULL,
    nome VARCHAR(100) NOT NULL,
    CONSTRAINT uq_subcategorias_clientes UNIQUE (categoria_id, nome),
    CONSTRAINT fk_subcategorias_clientes_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias_clientes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    subcategoria_id SMALLINT UNSIGNED NOT NULL,
    data_cadastro DATE NOT NULL,
    nome_fantasia VARCHAR(200) NOT NULL,
    razao_social VARCHAR(200) NOT NULL,
    cnpj VARCHAR(18) NOT NULL,
    inscricao_estadual VARCHAR(20),
    inscricao_municipal VARCHAR(20),
    telefone VARCHAR(20),
    email VARCHAR(150),
    limite_credito DECIMAL(19,4) NOT NULL DEFAULT 0,
    situacao_cadastral VARCHAR(50),
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    observacao TEXT,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_clientes_empresa_cnpj UNIQUE (empresa_id, cnpj),
    CONSTRAINT fk_clientes_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_clientes_subcategoria
        FOREIGN KEY (subcategoria_id) REFERENCES subcategorias_clientes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE tipos_endereco (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL,
    CONSTRAINT uq_tipos_endereco_tipo UNIQUE (tipo)
) ENGINE=InnoDB;

CREATE TABLE enderecos_clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    tipo_endereco_id TINYINT UNSIGNED NOT NULL,
    logradouro VARCHAR(200) NOT NULL,
    numero VARCHAR(20),
    complemento VARCHAR(200),
    bairro_id INT UNSIGNED NOT NULL,
    cep VARCHAR(9) NOT NULL,
    contato VARCHAR(100),
    telefone VARCHAR(20),
    celular VARCHAR(20),
    email VARCHAR(150),
    principal BOOLEAN NOT NULL DEFAULT FALSE,
    observacao TEXT,
    CONSTRAINT fk_enderecos_clientes_cliente
        FOREIGN KEY (cliente_id) REFERENCES clientes(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_enderecos_clientes_tipo
        FOREIGN KEY (tipo_endereco_id) REFERENCES tipos_endereco(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_enderecos_clientes_bairro
        FOREIGN KEY (bairro_id) REFERENCES bairros(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE departamentos_clientes (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    CONSTRAINT uq_departamentos_clientes_nome UNIQUE (nome)
) ENGINE=InnoDB;

CREATE TABLE contatos_clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    departamento_id SMALLINT UNSIGNED NOT NULL,
    nome VARCHAR(100) NOT NULL,
    telefone VARCHAR(20),
    celular VARCHAR(20),
    email VARCHAR(150),
    observacao TEXT,
    CONSTRAINT uq_contatos_clientes UNIQUE (cliente_id, departamento_id, nome),
    CONSTRAINT fk_contatos_clientes_cliente
        FOREIGN KEY (cliente_id) REFERENCES clientes(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_contatos_clientes_departamento
        FOREIGN KEY (departamento_id) REFERENCES departamentos_clientes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE vendedores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NULL,
    nome VARCHAR(200) NOT NULL,
    cpf VARCHAR(14) NOT NULL,
    email VARCHAR(150),
    telefone VARCHAR(20),
    celular VARCHAR(20),
    logradouro VARCHAR(200),
    numero VARCHAR(20),
    complemento VARCHAR(200),
    bairro_id INT UNSIGNED,
    cep VARCHAR(9),
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    observacao TEXT,
    CONSTRAINT uq_vendedores_empresa_cpf UNIQUE (empresa_id, cpf),
    CONSTRAINT uq_vendedores_usuario UNIQUE (usuario_id),
    CONSTRAINT fk_vendedores_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_vendedores_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_vendedores_bairro
        FOREIGN KEY (bairro_id) REFERENCES bairros(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE representadas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    nome VARCHAR(200) NOT NULL,
    cnpj VARCHAR(18) NOT NULL,
    inscricao_estadual VARCHAR(20),
    logradouro VARCHAR(200),
    numero VARCHAR(20),
    complemento VARCHAR(200),
    bairro_id INT UNSIGNED,
    cep VARCHAR(9),
    telefone VARCHAR(20),
    email VARCHAR(150),
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    observacao TEXT,
    CONSTRAINT uq_representadas_empresa_cnpj UNIQUE (empresa_id, cnpj),
    CONSTRAINT fk_representadas_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_representadas_bairro
        FOREIGN KEY (bairro_id) REFERENCES bairros(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE contatos_representadas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    representada_id INT UNSIGNED NOT NULL,
    nome VARCHAR(200) NOT NULL,
    telefone VARCHAR(20),
    celular VARCHAR(20),
    email VARCHAR(150),
    observacao TEXT,
    CONSTRAINT fk_contatos_representadas_representada
        FOREIGN KEY (representada_id) REFERENCES representadas(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE transportadoras (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    nome_fantasia VARCHAR(200) NOT NULL,
    razao_social VARCHAR(200) NOT NULL,
    cnpj VARCHAR(18) NOT NULL,
    inscricao_estadual VARCHAR(20),
    logradouro VARCHAR(200),
    numero VARCHAR(20),
    complemento VARCHAR(200),
    bairro_id INT UNSIGNED,
    cep VARCHAR(9),
    telefone VARCHAR(20),
    email VARCHAR(150),
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    observacao TEXT,
    CONSTRAINT uq_transportadoras_empresa_cnpj UNIQUE (empresa_id, cnpj),
    CONSTRAINT fk_transportadoras_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_transportadoras_bairro
        FOREIGN KEY (bairro_id) REFERENCES bairros(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

/*==============================================================*/
/* PRODUTOS, PREÇOS, POLÍTICAS E PROMOÇÕES                       */
/*==============================================================*/

CREATE TABLE categorias_produtos (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    CONSTRAINT uq_categorias_produtos_nome UNIQUE (nome)
) ENGINE=InnoDB;

CREATE TABLE subcategorias_produtos (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id SMALLINT UNSIGNED NOT NULL,
    nome VARCHAR(200) NOT NULL,
    CONSTRAINT uq_subcategorias_produtos UNIQUE (categoria_id, nome),
    CONSTRAINT fk_subcategorias_produtos_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias_produtos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE produtos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    representada_id INT UNSIGNED NOT NULL,
    subcategoria_id SMALLINT UNSIGNED NOT NULL,
    sku VARCHAR(100) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    preco_referencia DECIMAL(19,4) NOT NULL DEFAULT 0,
    ipi_percentual DECIMAL(9,4) NOT NULL DEFAULT 0,
    icms_percentual DECIMAL(9,4) NOT NULL DEFAULT 0,
    cst VARCHAR(10),
    ncm VARCHAR(10),
    peso DECIMAL(19,4) NOT NULL DEFAULT 0,
    codigo_barras VARCHAR(50),
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    observacao TEXT,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_produtos_representada_sku UNIQUE (representada_id, sku),
    CONSTRAINT fk_produtos_representada
        FOREIGN KEY (representada_id) REFERENCES representadas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_produtos_subcategoria
        FOREIGN KEY (subcategoria_id) REFERENCES subcategorias_produtos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE skus_clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    produto_id INT UNSIGNED NOT NULL,
    sku_cliente VARCHAR(100) NOT NULL,
    CONSTRAINT uq_skus_clientes UNIQUE (cliente_id, produto_id, sku_cliente),
    CONSTRAINT fk_skus_clientes_cliente
        FOREIGN KEY (cliente_id) REFERENCES clientes(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_skus_clientes_produto
        FOREIGN KEY (produto_id) REFERENCES produtos(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tabelas_precos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    representada_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NULL,
    ativa BOOLEAN NOT NULL DEFAULT TRUE,
    observacao TEXT,
    CONSTRAINT ck_tabelas_precos_periodo CHECK (data_fim IS NULL OR data_fim >= data_inicio),
    CONSTRAINT uq_tabelas_precos UNIQUE (representada_id, nome, data_inicio),
    CONSTRAINT fk_tabelas_precos_representada
        FOREIGN KEY (representada_id) REFERENCES representadas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE itens_tabelas_precos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tabela_preco_id INT UNSIGNED NOT NULL,
    produto_id INT UNSIGNED NOT NULL,
    preco DECIMAL(19,4) NOT NULL,
    desconto_maximo_percentual DECIMAL(9,4) NOT NULL DEFAULT 0,
    CONSTRAINT ck_itens_tabelas_precos_valores CHECK (
        preco >= 0 AND desconto_maximo_percentual BETWEEN 0 AND 100
    ),
    CONSTRAINT uq_itens_tabelas_precos UNIQUE (tabela_preco_id, produto_id),
    CONSTRAINT fk_itens_tabelas_precos_tabela
        FOREIGN KEY (tabela_preco_id) REFERENCES tabelas_precos(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_itens_tabelas_precos_produto
        FOREIGN KEY (produto_id) REFERENCES produtos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE politicas_comerciais (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    representada_id INT UNSIGNED NOT NULL,
    tabela_preco_id INT UNSIGNED NOT NULL,
    desconto_padrao_percentual DECIMAL(9,4) NOT NULL DEFAULT 0,
    desconto_maximo_percentual DECIMAL(9,4) NOT NULL DEFAULT 0,
    data_inicio DATE NOT NULL,
    data_fim DATE NULL,
    ativa BOOLEAN NOT NULL DEFAULT TRUE,
    observacao TEXT,
    CONSTRAINT ck_politicas_comerciais_periodo CHECK (data_fim IS NULL OR data_fim >= data_inicio),
    CONSTRAINT ck_politicas_comerciais_descontos CHECK (
        desconto_padrao_percentual BETWEEN 0 AND 100
        AND desconto_maximo_percentual BETWEEN 0 AND 100
        AND desconto_padrao_percentual <= desconto_maximo_percentual
    ),
    CONSTRAINT uq_politicas_comerciais UNIQUE (cliente_id, representada_id, data_inicio),
    CONSTRAINT fk_politicas_comerciais_cliente
        FOREIGN KEY (cliente_id) REFERENCES clientes(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_politicas_comerciais_representada
        FOREIGN KEY (representada_id) REFERENCES representadas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_politicas_comerciais_tabela
        FOREIGN KEY (tabela_preco_id) REFERENCES tabelas_precos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE promocoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    representada_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    acumulativa BOOLEAN NOT NULL DEFAULT FALSE,
    ativa BOOLEAN NOT NULL DEFAULT TRUE,
    observacao TEXT,
    CONSTRAINT ck_promocoes_periodo CHECK (data_fim >= data_inicio),
    CONSTRAINT uq_promocoes UNIQUE (representada_id, nome, data_inicio),
    CONSTRAINT fk_promocoes_representada
        FOREIGN KEY (representada_id) REFERENCES representadas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE itens_promocoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    promocao_id INT UNSIGNED NOT NULL,
    produto_id INT UNSIGNED NOT NULL,
    preco_promocional DECIMAL(19,4) NULL,
    desconto_percentual DECIMAL(9,4) NULL,
    quantidade_minima DECIMAL(19,4) NOT NULL DEFAULT 1,
    CONSTRAINT ck_itens_promocoes CHECK (
        quantidade_minima > 0
        AND (preco_promocional IS NOT NULL OR desconto_percentual IS NOT NULL)
        AND (desconto_percentual IS NULL OR desconto_percentual BETWEEN 0 AND 100)
        AND (preco_promocional IS NULL OR preco_promocional >= 0)
    ),
    CONSTRAINT uq_itens_promocoes UNIQUE (promocao_id, produto_id),
    CONSTRAINT fk_itens_promocoes_promocao
        FOREIGN KEY (promocao_id) REFERENCES promocoes(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_itens_promocoes_produto
        FOREIGN KEY (produto_id) REFERENCES produtos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

/*==============================================================*/
/* PEDIDOS E WORKFLOW                                           */
/*==============================================================*/

CREATE TABLE formas_pagamento (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    representada_id INT UNSIGNED NULL,
    nome VARCHAR(100) NOT NULL,
    ativa BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT uq_formas_pagamento UNIQUE (representada_id, nome),
    CONSTRAINT fk_formas_pagamento_representada
        FOREIGN KEY (representada_id) REFERENCES representadas(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tipos_frete (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(20) NOT NULL,
    CONSTRAINT uq_tipos_frete_nome UNIQUE (nome)
) ENGINE=InnoDB;

CREATE TABLE tipos_pedido (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    CONSTRAINT uq_tipos_pedido_nome UNIQUE (nome)
) ENGINE=InnoDB;

CREATE TABLE status_pedido (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    ordem_fluxo TINYINT UNSIGNED NOT NULL,
    finalizador BOOLEAN NOT NULL DEFAULT FALSE,
    CONSTRAINT uq_status_pedido_nome UNIQUE (nome),
    CONSTRAINT uq_status_pedido_ordem UNIQUE (ordem_fluxo)
) ENGINE=InnoDB;

CREATE TABLE pedidos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    numero_interno VARCHAR(30) NOT NULL,
    numero_representada VARCHAR(30),
    data_pedido DATE NOT NULL,
    data_programada DATE NULL,
    ordem_compra VARCHAR(30),
    cliente_id INT UNSIGNED NOT NULL,
    representada_id INT UNSIGNED NOT NULL,
    tabela_preco_id INT UNSIGNED NOT NULL,
    forma_pagamento_id SMALLINT UNSIGNED NOT NULL,
    transportadora_id INT UNSIGNED NULL,
    vendedor_id INT UNSIGNED NOT NULL,
    tipo_frete_id TINYINT UNSIGNED NOT NULL,
    tipo_pedido_id TINYINT UNSIGNED NOT NULL,
    status_id TINYINT UNSIGNED NOT NULL,
    desconto_geral_percentual DECIMAL(9,4) NOT NULL DEFAULT 0,
    desconto_adicional_percentual DECIMAL(9,4) NOT NULL DEFAULT 0,
    valor_produtos DECIMAL(19,4) NOT NULL DEFAULT 0,
    valor_ipi DECIMAL(19,4) NOT NULL DEFAULT 0,
    valor_total DECIMAL(19,4) NOT NULL DEFAULT 0,
    observacao TEXT,
    criado_por INT UNSIGNED NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT ck_pedidos_descontos CHECK (
        desconto_geral_percentual BETWEEN 0 AND 100
        AND desconto_adicional_percentual BETWEEN 0 AND 100
    ),
    CONSTRAINT uq_pedidos_empresa_numero UNIQUE (empresa_id, numero_interno),
    CONSTRAINT uq_pedidos_representada_numero UNIQUE (representada_id, numero_representada),
    CONSTRAINT fk_pedidos_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pedidos_cliente
        FOREIGN KEY (cliente_id) REFERENCES clientes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pedidos_representada
        FOREIGN KEY (representada_id) REFERENCES representadas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pedidos_tabela_preco
        FOREIGN KEY (tabela_preco_id) REFERENCES tabelas_precos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pedidos_forma_pagamento
        FOREIGN KEY (forma_pagamento_id) REFERENCES formas_pagamento(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pedidos_transportadora
        FOREIGN KEY (transportadora_id) REFERENCES transportadoras(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_pedidos_vendedor
        FOREIGN KEY (vendedor_id) REFERENCES vendedores(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pedidos_tipo_frete
        FOREIGN KEY (tipo_frete_id) REFERENCES tipos_frete(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pedidos_tipo_pedido
        FOREIGN KEY (tipo_pedido_id) REFERENCES tipos_pedido(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pedidos_status
        FOREIGN KEY (status_id) REFERENCES status_pedido(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pedidos_criado_por
        FOREIGN KEY (criado_por) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE itens_pedidos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id BIGINT UNSIGNED NOT NULL,
    sequencia SMALLINT UNSIGNED NOT NULL,
    produto_id INT UNSIGNED NOT NULL,
    promocao_id INT UNSIGNED NULL,
    sku_cliente VARCHAR(100),
    quantidade DECIMAL(19,4) NOT NULL,
    preco_tabela DECIMAL(19,4) NOT NULL,
    desconto_politica_percentual DECIMAL(9,4) NOT NULL DEFAULT 0,
    desconto_promocao_percentual DECIMAL(9,4) NOT NULL DEFAULT 0,
    desconto_adicional_percentual DECIMAL(9,4) NOT NULL DEFAULT 0,
    preco_unitario_final DECIMAL(19,4) NOT NULL,
    ipi_percentual DECIMAL(9,4) NOT NULL DEFAULT 0,
    valor_total_sem_ipi DECIMAL(19,4) NOT NULL,
    valor_ipi DECIMAL(19,4) NOT NULL DEFAULT 0,
    valor_total_com_ipi DECIMAL(19,4) NOT NULL,
    observacao VARCHAR(400),
    CONSTRAINT ck_itens_pedidos_valores CHECK (
        quantidade > 0
        AND preco_tabela >= 0
        AND preco_unitario_final >= 0
        AND desconto_politica_percentual BETWEEN 0 AND 100
        AND desconto_promocao_percentual BETWEEN 0 AND 100
        AND desconto_adicional_percentual BETWEEN 0 AND 100
        AND ipi_percentual BETWEEN 0 AND 100
    ),
    CONSTRAINT uq_itens_pedidos_sequencia UNIQUE (pedido_id, sequencia),
    CONSTRAINT fk_itens_pedidos_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_itens_pedidos_produto
        FOREIGN KEY (produto_id) REFERENCES produtos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_itens_pedidos_promocao
        FOREIGN KEY (promocao_id) REFERENCES promocoes(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE movimentacoes_pedidos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id BIGINT UNSIGNED NOT NULL,
    status_anterior_id TINYINT UNSIGNED NULL,
    status_novo_id TINYINT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    data_movimentacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacao TEXT,
    CONSTRAINT fk_movimentacoes_pedidos_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_movimentacoes_pedidos_status_anterior
        FOREIGN KEY (status_anterior_id) REFERENCES status_pedido(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_movimentacoes_pedidos_status_novo
        FOREIGN KEY (status_novo_id) REFERENCES status_pedido(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_movimentacoes_pedidos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

/*==============================================================*/
/* FATURAMENTO E ENTREGA                                        */
/*==============================================================*/

CREATE TABLE faturamentos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id BIGINT UNSIGNED NOT NULL,
    representada_id INT UNSIGNED NOT NULL,
    numero_nf VARCHAR(30) NOT NULL,
    serie_nf VARCHAR(10),
    chave_nfe VARCHAR(44),
    data_emissao DATE NOT NULL,
    valor_produtos DECIMAL(19,4) NOT NULL DEFAULT 0,
    valor_ipi DECIMAL(19,4) NOT NULL DEFAULT 0,
    valor_total DECIMAL(19,4) NOT NULL DEFAULT 0,
    previsao_entrega DATE NULL,
    data_entrega DATE NULL,
    observacao TEXT,
    criado_por INT UNSIGNED NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_faturamentos_representada_nf UNIQUE (representada_id, numero_nf, serie_nf),
    CONSTRAINT uq_faturamentos_chave_nfe UNIQUE (chave_nfe),
    CONSTRAINT fk_faturamentos_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_faturamentos_representada
        FOREIGN KEY (representada_id) REFERENCES representadas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_faturamentos_criado_por
        FOREIGN KEY (criado_por) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE itens_faturamentos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    faturamento_id BIGINT UNSIGNED NOT NULL,
    item_pedido_id BIGINT UNSIGNED NOT NULL,
    quantidade_faturada DECIMAL(19,4) NOT NULL,
    valor_unitario DECIMAL(19,4) NOT NULL,
    valor_total DECIMAL(19,4) NOT NULL,
    CONSTRAINT ck_itens_faturamentos_valores CHECK (
        quantidade_faturada > 0 AND valor_unitario >= 0 AND valor_total >= 0
    ),
    CONSTRAINT uq_itens_faturamentos UNIQUE (faturamento_id, item_pedido_id),
    CONSTRAINT fk_itens_faturamentos_faturamento
        FOREIGN KEY (faturamento_id) REFERENCES faturamentos(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_itens_faturamentos_item_pedido
        FOREIGN KEY (item_pedido_id) REFERENCES itens_pedidos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

/*==============================================================*/
/* COMISSÕES                                                    */
/*==============================================================*/

CREATE TABLE regras_comissoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    representada_id INT UNSIGNED NOT NULL,
    vendedor_id INT UNSIGNED NULL,
    produto_id INT UNSIGNED NULL,
    percentual DECIMAL(9,4) NOT NULL,
    base_calculo ENUM('SEM_IPI','COM_IPI','LIQUIDO_DESCONTOS') NOT NULL DEFAULT 'SEM_IPI',
    data_inicio DATE NOT NULL,
    data_fim DATE NULL,
    prioridade SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    ativa BOOLEAN NOT NULL DEFAULT TRUE,
    observacao TEXT,
    CONSTRAINT ck_regras_comissoes_percentual CHECK (percentual BETWEEN 0 AND 100),
    CONSTRAINT ck_regras_comissoes_periodo CHECK (data_fim IS NULL OR data_fim >= data_inicio),
    CONSTRAINT fk_regras_comissoes_representada
        FOREIGN KEY (representada_id) REFERENCES representadas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_regras_comissoes_vendedor
        FOREIGN KEY (vendedor_id) REFERENCES vendedores(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_regras_comissoes_produto
        FOREIGN KEY (produto_id) REFERENCES produtos(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE status_comissao (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(30) NOT NULL,
    CONSTRAINT uq_status_comissao_nome UNIQUE (nome)
) ENGINE=InnoDB;

CREATE TABLE comissoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id BIGINT UNSIGNED NOT NULL,
    faturamento_id BIGINT UNSIGNED NOT NULL,
    item_faturamento_id BIGINT UNSIGNED NULL,
    vendedor_id INT UNSIGNED NOT NULL,
    representada_id INT UNSIGNED NOT NULL,
    regra_comissao_id INT UNSIGNED NULL,
    base_calculo DECIMAL(19,4) NOT NULL,
    percentual DECIMAL(9,4) NOT NULL,
    valor_comissao DECIMAL(19,4) NOT NULL,
    status_id TINYINT UNSIGNED NOT NULL,
    data_previsao DATE NULL,
    data_recebimento DATE NULL,
    observacao TEXT,
    CONSTRAINT ck_comissoes_valores CHECK (
        base_calculo >= 0 AND percentual BETWEEN 0 AND 100 AND valor_comissao >= 0
    ),
    CONSTRAINT fk_comissoes_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_comissoes_faturamento
        FOREIGN KEY (faturamento_id) REFERENCES faturamentos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_comissoes_item_faturamento
        FOREIGN KEY (item_faturamento_id) REFERENCES itens_faturamentos(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_comissoes_vendedor
        FOREIGN KEY (vendedor_id) REFERENCES vendedores(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_comissoes_representada
        FOREIGN KEY (representada_id) REFERENCES representadas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_comissoes_regra
        FOREIGN KEY (regra_comissao_id) REFERENCES regras_comissoes(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_comissoes_status
        FOREIGN KEY (status_id) REFERENCES status_comissao(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

/*==============================================================*/
/* AGENDA, ROTEIROS E VISITAS                                   */
/*==============================================================*/

CREATE TABLE status_visita (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(30) NOT NULL,
    CONSTRAINT uq_status_visita_nome UNIQUE (nome)
) ENGINE=InnoDB;

CREATE TABLE visitas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendedor_id INT UNSIGNED NOT NULL,
    cliente_id INT UNSIGNED NOT NULL,
    status_id TINYINT UNSIGNED NOT NULL,
    data_prevista DATETIME NOT NULL,
    data_inicio DATETIME NULL,
    data_fim DATETIME NULL,
    objetivo TEXT,
    resultado TEXT,
    latitude_checkin DECIMAL(10,7),
    longitude_checkin DECIMAL(10,7),
    latitude_checkout DECIMAL(10,7),
    longitude_checkout DECIMAL(10,7),
    observacao TEXT,
    CONSTRAINT ck_visitas_periodo CHECK (
        data_fim IS NULL OR data_inicio IS NULL OR data_fim >= data_inicio
    ),
    CONSTRAINT fk_visitas_vendedor
        FOREIGN KEY (vendedor_id) REFERENCES vendedores(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_visitas_cliente
        FOREIGN KEY (cliente_id) REFERENCES clientes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_visitas_status
        FOREIGN KEY (status_id) REFERENCES status_visita(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE roteiros (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendedor_id INT UNSIGNED NOT NULL,
    data_roteiro DATE NOT NULL,
    descricao VARCHAR(255),
    status ENUM('PLANEJADO','EM_ANDAMENTO','CONCLUIDO','CANCELADO') NOT NULL DEFAULT 'PLANEJADO',
    CONSTRAINT uq_roteiros_vendedor_data UNIQUE (vendedor_id, data_roteiro),
    CONSTRAINT fk_roteiros_vendedor
        FOREIGN KEY (vendedor_id) REFERENCES vendedores(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE itens_roteiros (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    roteiro_id BIGINT UNSIGNED NOT NULL,
    cliente_id INT UNSIGNED NOT NULL,
    ordem_visita SMALLINT UNSIGNED NOT NULL,
    horario_previsto TIME NULL,
    observacao VARCHAR(255),
    CONSTRAINT uq_itens_roteiros_ordem UNIQUE (roteiro_id, ordem_visita),
    CONSTRAINT fk_itens_roteiros_roteiro
        FOREIGN KEY (roteiro_id) REFERENCES roteiros(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_itens_roteiros_cliente
        FOREIGN KEY (cliente_id) REFERENCES clientes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

/*==============================================================*/
/* IMPORTAÇÕES E INTEGRAÇÕES                                    */
/*==============================================================*/

CREATE TABLE importacoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    representada_id INT UNSIGNED NULL,
    usuario_id INT UNSIGNED NOT NULL,
    tipo ENUM('MANUAL','EXCEL','CSV','API') NOT NULL,
    entidade ENUM('PRODUTOS','PREÇOS','CLIENTES','OUTROS') NOT NULL,
    nome_arquivo VARCHAR(255),
    status ENUM('PENDENTE','VALIDANDO','PROCESSANDO','CONCLUIDA','CONCLUIDA_COM_ERROS','FALHA') NOT NULL DEFAULT 'PENDENTE',
    total_registros INT UNSIGNED NOT NULL DEFAULT 0,
    registros_processados INT UNSIGNED NOT NULL DEFAULT 0,
    registros_com_erro INT UNSIGNED NOT NULL DEFAULT 0,
    iniciado_em DATETIME NULL,
    finalizado_em DATETIME NULL,
    CONSTRAINT fk_importacoes_representada
        FOREIGN KEY (representada_id) REFERENCES representadas(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_importacoes_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE erros_importacoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    importacao_id BIGINT UNSIGNED NOT NULL,
    numero_linha INT UNSIGNED,
    campo VARCHAR(100),
    mensagem VARCHAR(500) NOT NULL,
    conteudo_original JSON,
    CONSTRAINT fk_erros_importacoes_importacao
        FOREIGN KEY (importacao_id) REFERENCES importacoes(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE consultas_cnpj (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    cnpj VARCHAR(18) NOT NULL,
    provedor VARCHAR(50) NOT NULL,
    sucesso BOOLEAN NOT NULL,
    resposta JSON,
    consultado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_consultas_cnpj_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_consultas_cnpj_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

/*==============================================================*/
/* LOG DE USUÁRIOS                                              */
/*==============================================================*/

CREATE TABLE logs_usuarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NULL,
    acao VARCHAR(100) NOT NULL,
    entidade VARCHAR(100),
    registro_id VARCHAR(100),
    dados_anteriores JSON,
    dados_novos JSON,
    endereco_ip VARCHAR(45),
    user_agent VARCHAR(500),
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_logs_usuarios_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_logs_usuarios_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

/*==============================================================*/
/* ÍNDICES DE PERFORMANCE                                       */
/*==============================================================*/

CREATE INDEX idx_clientes_empresa_nome ON clientes (empresa_id, nome_fantasia);
CREATE INDEX idx_clientes_razao_social ON clientes (razao_social);
CREATE INDEX idx_produtos_descricao ON produtos (descricao);
CREATE INDEX idx_produtos_codigo_barras ON produtos (codigo_barras);
CREATE INDEX idx_tabelas_precos_vigencia ON tabelas_precos (representada_id, data_inicio, data_fim, ativa);
CREATE INDEX idx_politicas_vigencia ON politicas_comerciais (cliente_id, representada_id, data_inicio, data_fim, ativa);
CREATE INDEX idx_promocoes_vigencia ON promocoes (representada_id, data_inicio, data_fim, ativa);
CREATE INDEX idx_pedidos_data ON pedidos (empresa_id, data_pedido);
CREATE INDEX idx_pedidos_cliente ON pedidos (cliente_id);
CREATE INDEX idx_pedidos_vendedor ON pedidos (vendedor_id);
CREATE INDEX idx_pedidos_status ON pedidos (status_id);
CREATE INDEX idx_itens_pedidos_produto ON itens_pedidos (produto_id);
CREATE INDEX idx_movimentacoes_pedido_data ON movimentacoes_pedidos (pedido_id, data_movimentacao);
CREATE INDEX idx_faturamentos_pedido ON faturamentos (pedido_id);
CREATE INDEX idx_comissoes_vendedor_status ON comissoes (vendedor_id, status_id);
CREATE INDEX idx_comissoes_previsao ON comissoes (data_previsao);
CREATE INDEX idx_visitas_vendedor_data ON visitas (vendedor_id, data_prevista);
CREATE INDEX idx_logs_empresa_data ON logs_usuarios (empresa_id, criado_em);
CREATE INDEX idx_logs_entidade_registro ON logs_usuarios (entidade, registro_id);

SET FOREIGN_KEY_CHECKS = 1;
=======
SET NAMES utf8mb4;
SET time_zone = '-03:00';

CREATE TABLE empresas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  razao_social VARCHAR(200) NOT NULL,
  nome_fantasia VARCHAR(200) NOT NULL,
  cnpj VARCHAR(18) NULL,
  email VARCHAR(200) NULL,
  telefone VARCHAR(20) NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_empresas_cnpj (cnpj)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE perfis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  descricao VARCHAR(255) NULL,
  UNIQUE KEY uq_perfis_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE permissoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(100) NOT NULL,
  descricao VARCHAR(255) NOT NULL,
  UNIQUE KEY uq_permissoes_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE perfil_permissoes (
  perfil_id INT NOT NULL,
  permissao_id INT NOT NULL,
  PRIMARY KEY (perfil_id, permissao_id),
  CONSTRAINT fk_pp_perfil FOREIGN KEY (perfil_id) REFERENCES perfis(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_pp_permissao FOREIGN KEY (permissao_id) REFERENCES permissoes(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  perfil_id INT NOT NULL,
  nome VARCHAR(200) NOT NULL,
  email VARCHAR(200) NOT NULL,
  senha VARCHAR(255) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  ultimo_login_em DATETIME NULL,
  data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_usuarios_email (email),
  KEY idx_usuarios_empresa (empresa_id),
  KEY idx_usuarios_perfil (perfil_id),
  CONSTRAINT fk_usuarios_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_usuarios_perfil FOREIGN KEY (perfil_id) REFERENCES perfis(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE logs_usuarios (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  usuario_id INT NULL,
  acao VARCHAR(100) NOT NULL,
  entidade VARCHAR(100) NULL,
  registro_id VARCHAR(100) NULL,
  dados_anteriores JSON NULL,
  dados_novos JSON NULL,
  endereco_ip VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_logs_empresa (empresa_id),
  KEY idx_logs_usuario (usuario_id),
  CONSTRAINT fk_logs_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_logs_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE estados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  sigla CHAR(2) NOT NULL,
  UNIQUE KEY uq_estados_nome (nome),
  UNIQUE KEY uq_estados_sigla (sigla)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE cidades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  estado_id INT NOT NULL,
  UNIQUE KEY uq_cidades_estado (estado_id,nome),
  CONSTRAINT fk_cidades_estado FOREIGN KEY (estado_id) REFERENCES estados(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE bairros (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(200) NOT NULL,
  cidade_id INT NOT NULL,
  UNIQUE KEY uq_bairros_cidade (cidade_id,nome),
  CONSTRAINT fk_bairros_cidade FOREIGN KEY (cidade_id) REFERENCES cidades(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE representadas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  nome VARCHAR(200) NOT NULL,
  cnpj VARCHAR(18) NOT NULL,
  inscricao_estadual VARCHAR(20) NULL,
  logradouro VARCHAR(200) NULL,
  numero VARCHAR(20) NULL,
  complemento VARCHAR(200) NULL,
  bairro_id INT NULL,
  cep VARCHAR(9) NULL,
  telefone VARCHAR(20) NULL,
  email VARCHAR(150) NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  observacao TEXT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_representadas_empresa_cnpj (empresa_id,cnpj),
  KEY idx_representadas_empresa (empresa_id),
  KEY idx_representadas_bairro (bairro_id),
  CONSTRAINT fk_representadas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_representadas_bairro FOREIGN KEY (bairro_id) REFERENCES bairros(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE subcategorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  categoria_id INT NOT NULL,
  UNIQUE KEY uq_subcategorias_categoria (categoria_id,nome),
  CONSTRAINT fk_subcategorias_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  datacad DATE NOT NULL,
  nomefantasia VARCHAR(200) NOT NULL,
  razaosocial VARCHAR(200) NOT NULL,
  cnpj VARCHAR(18) NOT NULL,
  inscr_est VARCHAR(20) NOT NULL,
  inscr_mun VARCHAR(20) NULL,
  telefone VARCHAR(20) NULL,
  email VARCHAR(100) NOT NULL,
  subcategorias_id INT NOT NULL,
  credito DECIMAL(19,4) NOT NULL DEFAULT 0,
  obs TEXT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_clientes_empresa_cnpj (empresa_id,cnpj),
  CONSTRAINT fk_clientes_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_clientes_subcategoria FOREIGN KEY (subcategorias_id) REFERENCES subcategorias(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE vendedores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  nome VARCHAR(200) NOT NULL,
  cpf VARCHAR(14) NOT NULL,
  endereco VARCHAR(200) NOT NULL,
  complemento VARCHAR(200) NULL,
  numero VARCHAR(10) NULL,
  bairro_id INT NOT NULL,
  cep VARCHAR(9) NOT NULL,
  telefone VARCHAR(20) NULL,
  celular VARCHAR(20) NULL,
  email VARCHAR(200) NOT NULL,
  obs TEXT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_vendedores_empresa_cpf (empresa_id,cpf),
  CONSTRAINT fk_vendedores_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_vendedores_bairro FOREIGN KEY (bairro_id) REFERENCES bairros(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE transportes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  nomefantasia VARCHAR(200) NOT NULL,
  razaosocial VARCHAR(200) NOT NULL,
  cnpj VARCHAR(18) NOT NULL,
  inscr_est VARCHAR(20) NOT NULL,
  endereco VARCHAR(200) NOT NULL,
  complemento VARCHAR(200) NULL,
  numero VARCHAR(20) NULL,
  bairro_id INT NOT NULL,
  cep VARCHAR(9) NOT NULL,
  obs TEXT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_transportes_empresa_cnpj (empresa_id,cnpj),
  CONSTRAINT fk_transportes_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_transportes_bairro FOREIGN KEY (bairro_id) REFERENCES bairros(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE frete (
  id INT AUTO_INCREMENT PRIMARY KEY,
  frete VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE pagamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pagamento VARCHAR(50) NOT NULL,
  representada_id INT NULL,
  UNIQUE KEY uq_pagamentos_representada (representada_id,pagamento),
  CONSTRAINT fk_pagamentos_representada FOREIGN KEY (representada_id) REFERENCES representadas(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE tipopedidos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE statuspedidos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  status VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE pedidos (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  data DATE NOT NULL,
  dataprograma DATE NULL,
  numero VARCHAR(20) NULL,
  oc VARCHAR(20) NULL,
  cliente_id INT NOT NULL,
  representada_id INT NOT NULL,
  pagamentos_id INT NOT NULL,
  transportes_id INT NOT NULL,
  vendedor_id INT NOT NULL,
  frete_id INT NOT NULL,
  tipopedido_id INT NOT NULL,
  desconto DECIMAL(19,4) NULL,
  desc_adicional DECIMAL(19,4) NULL,
  status_id INT NOT NULL,
  valor_total DECIMAL(19,4) NULL,
  valor_total_cipi DECIMAL(19,4) NULL,
  obs VARCHAR(400) NULL,
  KEY idx_pedidos_empresa (empresa_id),
  CONSTRAINT fk_pedidos_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_pedidos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_pedidos_representada FOREIGN KEY (representada_id) REFERENCES representadas(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_pedidos_pagamento FOREIGN KEY (pagamentos_id) REFERENCES pagamentos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_pedidos_transporte FOREIGN KEY (transportes_id) REFERENCES transportes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_pedidos_vendedor FOREIGN KEY (vendedor_id) REFERENCES vendedores(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_pedidos_frete FOREIGN KEY (frete_id) REFERENCES frete(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_pedidos_tipo FOREIGN KEY (tipopedido_id) REFERENCES tipopedidos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_pedidos_status FOREIGN KEY (status_id) REFERENCES statuspedidos(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DELIMITER $$

CREATE TRIGGER bi_empresas BEFORE INSERT ON empresas FOR EACH ROW
BEGIN
  SET NEW.razao_social = UPPER(TRIM(NEW.razao_social));
  SET NEW.nome_fantasia = UPPER(TRIM(NEW.nome_fantasia));
  SET NEW.email = IF(NEW.email IS NULL, NULL, LOWER(TRIM(NEW.email)));
END$$
CREATE TRIGGER bu_empresas BEFORE UPDATE ON empresas FOR EACH ROW
BEGIN
  SET NEW.razao_social = UPPER(TRIM(NEW.razao_social));
  SET NEW.nome_fantasia = UPPER(TRIM(NEW.nome_fantasia));
  SET NEW.email = IF(NEW.email IS NULL, NULL, LOWER(TRIM(NEW.email)));
END$$

CREATE TRIGGER bi_usuarios BEFORE INSERT ON usuarios FOR EACH ROW
BEGIN
  SET NEW.nome = UPPER(TRIM(NEW.nome));
  SET NEW.email = LOWER(TRIM(NEW.email));
END$$
CREATE TRIGGER bu_usuarios BEFORE UPDATE ON usuarios FOR EACH ROW
BEGIN
  SET NEW.nome = UPPER(TRIM(NEW.nome));
  SET NEW.email = LOWER(TRIM(NEW.email));
END$$

CREATE TRIGGER bi_estados BEFORE INSERT ON estados FOR EACH ROW
BEGIN SET NEW.nome=UPPER(TRIM(NEW.nome)); SET NEW.sigla=UPPER(TRIM(NEW.sigla)); END$$
CREATE TRIGGER bu_estados BEFORE UPDATE ON estados FOR EACH ROW
BEGIN SET NEW.nome=UPPER(TRIM(NEW.nome)); SET NEW.sigla=UPPER(TRIM(NEW.sigla)); END$$
CREATE TRIGGER bi_cidades BEFORE INSERT ON cidades FOR EACH ROW
BEGIN SET NEW.nome=UPPER(TRIM(NEW.nome)); END$$
CREATE TRIGGER bu_cidades BEFORE UPDATE ON cidades FOR EACH ROW
BEGIN SET NEW.nome=UPPER(TRIM(NEW.nome)); END$$
CREATE TRIGGER bi_bairros BEFORE INSERT ON bairros FOR EACH ROW
BEGIN SET NEW.nome=UPPER(TRIM(NEW.nome)); END$$
CREATE TRIGGER bu_bairros BEFORE UPDATE ON bairros FOR EACH ROW
BEGIN SET NEW.nome=UPPER(TRIM(NEW.nome)); END$$

CREATE TRIGGER bi_representadas BEFORE INSERT ON representadas FOR EACH ROW
BEGIN
  SET NEW.nome=UPPER(TRIM(NEW.nome));
  SET NEW.inscricao_estadual=IF(NEW.inscricao_estadual IS NULL,NULL,UPPER(TRIM(NEW.inscricao_estadual)));
  SET NEW.logradouro=IF(NEW.logradouro IS NULL,NULL,UPPER(TRIM(NEW.logradouro)));
  SET NEW.numero=IF(NEW.numero IS NULL,NULL,UPPER(TRIM(NEW.numero)));
  SET NEW.complemento=IF(NEW.complemento IS NULL,NULL,UPPER(TRIM(NEW.complemento)));
  SET NEW.email=IF(NEW.email IS NULL,NULL,LOWER(TRIM(NEW.email)));
  SET NEW.observacao=IF(NEW.observacao IS NULL,NULL,UPPER(TRIM(NEW.observacao)));
END$$
CREATE TRIGGER bu_representadas BEFORE UPDATE ON representadas FOR EACH ROW
BEGIN
  SET NEW.nome=UPPER(TRIM(NEW.nome));
  SET NEW.inscricao_estadual=IF(NEW.inscricao_estadual IS NULL,NULL,UPPER(TRIM(NEW.inscricao_estadual)));
  SET NEW.logradouro=IF(NEW.logradouro IS NULL,NULL,UPPER(TRIM(NEW.logradouro)));
  SET NEW.numero=IF(NEW.numero IS NULL,NULL,UPPER(TRIM(NEW.numero)));
  SET NEW.complemento=IF(NEW.complemento IS NULL,NULL,UPPER(TRIM(NEW.complemento)));
  SET NEW.email=IF(NEW.email IS NULL,NULL,LOWER(TRIM(NEW.email)));
  SET NEW.observacao=IF(NEW.observacao IS NULL,NULL,UPPER(TRIM(NEW.observacao)));
END$$

DELIMITER ;
>>>>>>> 8ea7486 (chore: Correções)
