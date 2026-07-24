/*======================================================*/
/* DDL - CRIAÇÃO DE ESTRUTURAS E CONSTRAINTS (MySQL)    */
/*======================================================*/

CREATE TABLE estados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) UNIQUE NOT NULL,
    sigla CHAR(2) UNIQUE NOT NULL
) ENGINE=InnoDB;

CREATE TABLE cidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    estado_id INT NOT NULL,
    CONSTRAINT fk_cidades_estado FOREIGN KEY (estado_id) REFERENCES estados (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE bairros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    cidade_id INT NOT NULL,
    CONSTRAINT fk_bairros_cidade FOREIGN KEY (cidade_id) REFERENCES cidades (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_bairros UNIQUE (nome, cidade_id)
) ENGINE=InnoDB;

CREATE TABLE tipos_endereco (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) UNIQUE NOT NULL
) ENGINE=InnoDB;

CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) UNIQUE NOT NULL
) ENGINE=InnoDB;

CREATE TABLE subcategorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    categoria_id INT NOT NULL,
    CONSTRAINT fk_subcategorias_categorias FOREIGN KEY (categoria_id) REFERENCES categorias (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_subcategorias_categorias UNIQUE (categoria_id, nome)
) ENGINE=InnoDB;

CREATE TABLE depart_clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    departamento VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    datacad DATE NOT NULL,
    nomefantasia VARCHAR(200) NOT NULL,
    razaosocial VARCHAR(200) NOT NULL,
    cnpj VARCHAR(18) NOT NULL,
    inscr_est VARCHAR(20) NOT NULL,
    inscr_mun VARCHAR(20),
    telefone VARCHAR(20),
    email VARCHAR(100) NOT NULL,
    subcategorias_id INT NOT NULL,
    credito DECIMAL(19,4) NOT NULL,
    obs TEXT,
    CONSTRAINT fk_clientes_subcategorias FOREIGN KEY (subcategorias_id) REFERENCES subcategorias (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_clientes_cnpj_razao UNIQUE (cnpj, razaosocial)
) ENGINE=InnoDB;

CREATE TABLE enderecos_clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_endereco_id INT NOT NULL,
    cliente_id INT NOT NULL,
    endereco VARCHAR(200) NOT NULL,
    complemento VARCHAR(200),
    numero VARCHAR(20),
    bairro_id INT NOT NULL,
    cep VARCHAR(9) NOT NULL,
    contato VARCHAR(50) NOT NULL,
    telefone VARCHAR(20),
    celular VARCHAR(20) NOT NULL,
    email VARCHAR(150) NOT NULL,
    obs TEXT,
    CONSTRAINT fk_endcli_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_endcli_bairro FOREIGN KEY (bairro_id) REFERENCES bairros (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_endereco_tipo FOREIGN KEY (tipo_endereco_id) REFERENCES tipos_endereco (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE contatosclientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    departamento_id INT NOT NULL,
    nome VARCHAR(50) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    celular VARCHAR(20),
    email VARCHAR(50) NOT NULL,
    obs TEXT,
    CONSTRAINT fk_contatosclientes_clientes FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_contatos_departamento FOREIGN KEY (departamento_id) REFERENCES depart_clientes (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_contatos UNIQUE (cliente_id, departamento_id, nome)
) ENGINE=InnoDB;

CREATE TABLE vendedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    cpf VARCHAR(14) NOT NULL,
    endereco VARCHAR(200) NOT NULL,
    complemento VARCHAR(200),
    numero VARCHAR(10),
    bairro_id INT NOT NULL,
    cep VARCHAR(9) NOT NULL,
    telefone VARCHAR(20),
    celular VARCHAR(20),
    email VARCHAR(200) NOT NULL,
    obs TEXT,
    CONSTRAINT fk_vendedores_bairros FOREIGN KEY (bairro_id) REFERENCES bairros (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_vendedores_bairros UNIQUE (nome, bairro_id),
    CONSTRAINT uq_vendedores_cpf UNIQUE (cpf),
    CONSTRAINT uq_vendedores_telefone UNIQUE (telefone),
    CONSTRAINT uq_vendedores_celular UNIQUE (celular)
) ENGINE=InnoDB;

CREATE TABLE representadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    cnpj VARCHAR(18) NOT NULL,
    inscricao VARCHAR(20) NOT NULL,
    endereco VARCHAR(200) NOT NULL,
    bairro_id INT NOT NULL,
    cep VARCHAR(9) NOT NULL,
    telefone VARCHAR(20),
    email VARCHAR(200) NOT NULL,
    obs TEXT,
    CONSTRAINT fk_representadas_bairros FOREIGN KEY (bairro_id) REFERENCES bairros (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_representada UNIQUE (cnpj)
) ENGINE=InnoDB;

CREATE TABLE contato_representadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    telefone VARCHAR(20),
    celular VARCHAR(20) NOT NULL,
    email VARCHAR(200) NOT NULL,
    representada_id INT NOT NULL,
    obs TEXT,
    CONSTRAINT fk_contrep_representadas FOREIGN KEY (representada_id) REFERENCES representadas (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE categ_prod (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria VARCHAR(200) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE subcateg_prod (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subcategoria VARCHAR(200) NOT NULL,
    cagegoria_id INT NOT NULL,
    CONSTRAINT fk_subcategprod_categprod FOREIGN KEY (cagegoria_id) REFERENCES categ_prod (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_subcategprod_categprod UNIQUE (subcategoria, cagegoria_id)
) ENGINE=InnoDB;

CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(200) NOT NULL,
    descricao VARCHAR(200) NOT NULL,
    preco DECIMAL(19,4) NOT NULL,
    ipi DECIMAL(19,4) NOT NULL,
    icms DECIMAL(19,4) NOT NULL,
    cst INT NOT NULL,
    peso DECIMAL(19,4) NOT NULL,
    classfiscal VARCHAR(50) NOT NULL,
    codbarras VARCHAR(200) NOT NULL,
    obs TEXT,
    representada_id INT NOT NULL,
    subcategprod_id INT NOT NULL,
    CONSTRAINT fk_produto_representada FOREIGN KEY (representada_id) REFERENCES representadas (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_produto_subcateg FOREIGN KEY (subcategprod_id) REFERENCES subcateg_prod (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_produtos UNIQUE (sku, codbarras)
) ENGINE=InnoDB;

CREATE TABLE skus_do_cliente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    cliente_id INT NOT NULL,
    sku_do_cliente VARCHAR(50) NOT NULL,
    CONSTRAINT fk_skus_produto FOREIGN KEY (produto_id) REFERENCES produtos (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_skus_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pagamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pagamento VARCHAR(50) UNIQUE NOT NULL
) ENGINE=InnoDB;

CREATE TABLE transportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomefantasia VARCHAR(200) NOT NULL,
    razaosocial VARCHAR(200) NOT NULL UNIQUE,
    cnpj VARCHAR(18) NOT NULL UNIQUE,
    inscr_est VARCHAR(20) NOT NULL UNIQUE,
    endereco VARCHAR(200) NOT NULL,
    complemento VARCHAR(200),
    numero VARCHAR(20),
    bairro_id INT NOT NULL,
    cep VARCHAR(9) NOT NULL,
    obs TEXT,
    CONSTRAINT fk_transportes_bairro FOREIGN KEY (bairro_id) REFERENCES bairros (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE frete (
    id INT AUTO_INCREMENT PRIMARY KEY,
    frete VARCHAR(20) UNIQUE NOT NULL
) ENGINE=InnoDB;

CREATE TABLE tipopedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE statuspedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE pedidos (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    data DATE NOT NULL,
    dataprograma DATE,
    numero VARCHAR(20),
    oc VARCHAR(20),
    cliente_id INT NOT NULL,
    representada_id INT NOT NULL,
    pagamentos_id INT NOT NULL,
    transportes_id INT NOT NULL,
    vendedor_id INT NOT NULL,
    frete_id INT NOT NULL,
    tipopedido_id INT NOT NULL,
    desconto DECIMAL(19,4),
    desc_adicional DECIMAL(19,4),
    status_id INT NOT NULL,
    valor_total DECIMAL(19,4),
    valor_total_cipi DECIMAL(19,4),
    obs VARCHAR(400),
    CONSTRAINT fk_pedidos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pedidos_representadas FOREIGN KEY (representada_id) REFERENCES representadas (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pedidos_transportes FOREIGN KEY (transportes_id) REFERENCES transportes (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pedidos_vendedores FOREIGN KEY (vendedor_id) REFERENCES vendedores (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pedidos_frete FOREIGN KEY (frete_id) REFERENCES frete (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pedidos_tipopedido FOREIGN KEY (tipopedido_id) REFERENCES tipopedidos (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pedidos_status FOREIGN KEY (status_id) REFERENCES statuspedidos (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pedidos_pagamentos FOREIGN KEY (pagamentos_id) REFERENCES pagamentos (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_pedidos_numero UNIQUE (numero, representada_id)
) ENGINE=InnoDB;

CREATE TABLE itenspedidos (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    pedido_id BIGINT NOT NULL,
    produto_id INT NOT NULL,
    desconto DECIMAL(19,4),
    desc_adicional DECIMAL(19,4),
    quant DECIMAL(19,4) NOT NULL,
    preco DECIMAL(19,4) NOT NULL,
    ipi DECIMAL(19,4) NOT NULL,
    total DECIMAL(19,4) NOT NULL,
    totalcimp DECIMAL(19,4) NOT NULL,
    CONSTRAINT fk_itenspedidos_pedidos FOREIGN KEY (pedido_id) REFERENCES pedidos (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_itenspedidos_produtos FOREIGN KEY (produto_id) REFERENCES produtos (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_itenspedidos UNIQUE (pedido_id, produto_id)
) ENGINE=InnoDB;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    email VARCHAR(200) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_usuarios_email UNIQUE (email)
) ENGINE=InnoDB;

/* ====================================================== */
/* ÍNDICES DE PERFORMANCE NAS CHAVES ESTRANGEIRAS (FKS)   */
/* ====================================================== */
CREATE INDEX idx_bairros_cidade ON bairros (cidade_id);
CREATE INDEX idx_endcli_cliente ON enderecos_clientes (cliente_id);
CREATE INDEX idx_endcli_bairro ON enderecos_clientes (bairro_id);
CREATE INDEX idx_vendedores_bairro ON vendedores (bairro_id);
CREATE INDEX idx_representadas_bairro ON representadas (bairro_id);
CREATE INDEX idx_produtos_representada ON produtos (representada_id);
CREATE INDEX idx_produtos_subcateg ON produtos (subcategprod_id);
CREATE INDEX idx_pedidos_cliente ON pedidos (cliente_id);
CREATE INDEX idx_pedidos_representada ON pedidos (representada_id);
CREATE INDEX idx_pedidos_vendedor ON pedidos (vendedor_id);
CREATE INDEX idx_itenspedidos_pedido ON itenspedidos (pedido_id);
CREATE INDEX idx_itenspedidos_produto ON itenspedidos (produto_id);
