/*======================================================*/
/* DML - CARGA INICIAL DE DADOS (MySQL)                 */
/*======================================================*/

INSERT INTO estados (nome, sigla) VALUES 
('ACRE', 'AC'), ('ALAGOAS', 'AL'), ('AMAPA', 'AP'), ('AMAZONAS', 'AM'), 
('BAHIA', 'BH'), ('CEARA', 'CE'), ('DISTRITO FEDERAL', 'DF'), ('ESPIRITO SANTO', 'ES'), 
('GOIAS', 'GO'), ('MARANHAO', 'MA'), ('MATO GROSSO', 'MT'), ('MATO GROSSO DO SUL', 'MS'), 
('MINAS GERAIS', 'MG'), ('PARA', 'PA'), ('PARAIBA', 'PB'), ('PARANA', 'PR'), 
('PERNAMBUCO', 'PE'), ('PIAUI', 'PI'), ('RIO DE JANEIRO', 'RJ'), ('RIO GRANDE DO NORTE', 'RN'), 
('RIO GRANDE DO SUL', 'RS'), ('RONDONIA', 'RO'), ('RORAIMA', 'RR'), ('SANTA CATARINA', 'SC'), 
('SAO PAULO', 'SP'), ('SERGIPE', 'SE'), ('TOCANTINS', 'TO');

INSERT INTO cidades (nome, estado_id) VALUES 
('MINEIROS', (SELECT id FROM estados WHERE sigla = 'GO')),
('MONTIVIDIU', (SELECT id FROM estados WHERE sigla = 'GO')),
('SÃO PAULO', (SELECT id FROM estados WHERE sigla = 'SP'));

INSERT INTO bairros (nome, cidade_id) VALUES 
('CENTRO', (SELECT id FROM cidades WHERE nome = 'MINEIROS')),
('PARQUE AMAZONAS', (SELECT id FROM cidades WHERE nome = 'MINEIROS')),
('JARDIM AMERICA', (SELECT id FROM cidades WHERE nome = 'MINEIROS'));

INSERT INTO categorias(nome) VALUES 
('ATACADO'), ('VAREJO'), ('MRO'), ('HOME CENTER'), ('CONSTRUTORA');

INSERT INTO subcategorias(nome, categoria_id) VALUES 
('MATERIAIS DE CONSTRUÇÃO', (SELECT id FROM categorias WHERE nome = 'VAREJO')),
('MATERIAIS DE CONSTRUÇÃO', (SELECT id FROM categorias WHERE nome = 'ATACADO')),
('FERRAGISTA', (SELECT id FROM categorias WHERE nome = 'VAREJO')),
('FERRAMENTAS E PARAFUSOS', (SELECT id FROM categorias WHERE nome = 'VAREJO'));

INSERT INTO depart_clientes(departamento) VALUES 
('FINANCEIRO'), ('COMERCIAL');

INSERT INTO tipos_endereco(tipo) VALUES 
('FATURAMENTO'), ('ENTREGA'), ('COBRANCA');

INSERT INTO categ_prod(categoria) VALUES 
('FERRAMENTAS');

INSERT INTO frete (frete) VALUES 
('CIF'), ('FOB'), ('REDESPACHO');

INSERT INTO statuspedidos(status) VALUES 
('ABERTO'), ('FECHADO GANHO'), ('FECHADO PERDIDO'), ('FATURADO TOTAL'), ('FATURADO PARCIAL');

INSERT INTO usuarios (nome, email, senha) VALUES (
    'admin',
    'admin@sgv9.com.br',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
);
