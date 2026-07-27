<<<<<<< HEAD
/*==============================================================*/
/* SGV9 - DML V2                                                */
/* Carga inicial                                                */
/*==============================================================*/

USE sgv9;
SET NAMES utf8mb4;

START TRANSACTION;

/* Empresa inicial */
INSERT INTO empresas (razao_social, nome_fantasia, cnpj, telefone, email)
VALUES ('ESCRITÓRIO DE REPRESENTAÇÃO COMERCIAL LTDA',
        'SGV9 REPRESENTAÇÕES',
        '00.000.000/0001-00',
        NULL,
        'contato@sgv9.com.br');

/* Perfis */
INSERT INTO perfis (nome, descricao) VALUES
('ADMINISTRADOR', 'Acesso total ao sistema'),
('VENDEDOR', 'Acesso comercial aos próprios clientes, visitas, pedidos e comissões'),
('ASSISTENTE', 'Apoio operacional em cadastros, pedidos e acompanhamento'),
('FINANCEIRO', 'Acesso ao controle de faturamento e comissões'),
('CONSULTA', 'Acesso somente para leitura');

/* Permissões */
INSERT INTO permissoes (codigo, descricao) VALUES
('USUARIOS_GERENCIAR', 'Criar, editar, ativar e desativar usuários'),
('PERFIS_GERENCIAR', 'Gerenciar perfis e permissões'),
('CLIENTES_VISUALIZAR', 'Visualizar clientes'),
('CLIENTES_GERENCIAR', 'Criar e editar clientes'),
('REPRESENTADAS_VISUALIZAR', 'Visualizar representadas'),
('REPRESENTADAS_GERENCIAR', 'Criar e editar representadas'),
('PRODUTOS_VISUALIZAR', 'Visualizar produtos'),
('PRODUTOS_GERENCIAR', 'Criar e editar produtos'),
('PRECOS_VISUALIZAR', 'Visualizar tabelas de preços e promoções'),
('PRECOS_GERENCIAR', 'Criar e editar tabelas de preços, políticas e promoções'),
('PEDIDOS_VISUALIZAR', 'Visualizar pedidos'),
('PEDIDOS_GERENCIAR', 'Criar e editar pedidos'),
('PEDIDOS_APROVAR_DESCONTO', 'Aprovar descontos acima da política comercial'),
('FATURAMENTOS_VISUALIZAR', 'Visualizar faturamentos'),
('FATURAMENTOS_GERENCIAR', 'Registrar e editar faturamentos'),
('COMISSOES_VISUALIZAR', 'Visualizar comissões'),
('COMISSOES_GERENCIAR', 'Configurar, apurar e baixar comissões'),
('AGENDA_VISUALIZAR', 'Visualizar agenda, roteiros e visitas'),
('AGENDA_GERENCIAR', 'Criar e editar agenda, roteiros e visitas'),
('IMPORTACOES_EXECUTAR', 'Executar importações por Excel, CSV ou API'),
('LOGS_VISUALIZAR', 'Visualizar logs de usuários');

/* Administrador recebe todas as permissões */
INSERT INTO perfil_permissoes (perfil_id, permissao_id)
SELECT p.id, pe.id
FROM perfis p
CROSS JOIN permissoes pe
WHERE p.nome = 'ADMINISTRADOR';

/* Vendedor */
INSERT INTO perfil_permissoes (perfil_id, permissao_id)
SELECT p.id, pe.id
FROM perfis p
JOIN permissoes pe ON pe.codigo IN (
    'CLIENTES_VISUALIZAR',
    'CLIENTES_GERENCIAR',
    'REPRESENTADAS_VISUALIZAR',
    'PRODUTOS_VISUALIZAR',
    'PRECOS_VISUALIZAR',
    'PEDIDOS_VISUALIZAR',
    'PEDIDOS_GERENCIAR',
    'FATURAMENTOS_VISUALIZAR',
    'COMISSOES_VISUALIZAR',
    'AGENDA_VISUALIZAR',
    'AGENDA_GERENCIAR'
)
WHERE p.nome = 'VENDEDOR';

/* Assistente */
INSERT INTO perfil_permissoes (perfil_id, permissao_id)
SELECT p.id, pe.id
FROM perfis p
JOIN permissoes pe ON pe.codigo IN (
    'CLIENTES_VISUALIZAR',
    'CLIENTES_GERENCIAR',
    'REPRESENTADAS_VISUALIZAR',
    'REPRESENTADAS_GERENCIAR',
    'PRODUTOS_VISUALIZAR',
    'PRODUTOS_GERENCIAR',
    'PRECOS_VISUALIZAR',
    'PEDIDOS_VISUALIZAR',
    'PEDIDOS_GERENCIAR',
    'FATURAMENTOS_VISUALIZAR',
    'AGENDA_VISUALIZAR',
    'AGENDA_GERENCIAR',
    'IMPORTACOES_EXECUTAR'
)
WHERE p.nome = 'ASSISTENTE';

/* Financeiro */
INSERT INTO perfil_permissoes (perfil_id, permissao_id)
SELECT p.id, pe.id
FROM perfis p
JOIN permissoes pe ON pe.codigo IN (
    'CLIENTES_VISUALIZAR',
    'REPRESENTADAS_VISUALIZAR',
    'PEDIDOS_VISUALIZAR',
    'FATURAMENTOS_VISUALIZAR',
    'FATURAMENTOS_GERENCIAR',
    'COMISSOES_VISUALIZAR',
    'COMISSOES_GERENCIAR'
)
WHERE p.nome = 'FINANCEIRO';

/* Consulta */
INSERT INTO perfil_permissoes (perfil_id, permissao_id)
SELECT p.id, pe.id
FROM perfis p
JOIN permissoes pe ON pe.codigo IN (
    'CLIENTES_VISUALIZAR',
    'REPRESENTADAS_VISUALIZAR',
    'PRODUTOS_VISUALIZAR',
    'PRECOS_VISUALIZAR',
    'PEDIDOS_VISUALIZAR',
    'FATURAMENTOS_VISUALIZAR',
    'COMISSOES_VISUALIZAR',
    'AGENDA_VISUALIZAR'
)
WHERE p.nome = 'CONSULTA';

/* Usuário administrador inicial
   Senha do hash padrão do PHP: "password"
   Trocar imediatamente no primeiro acesso. */
INSERT INTO usuarios (empresa_id, perfil_id, nome, email, senha)
SELECT e.id, p.id, 'Administrador', 'admin@sgv9.com.br',
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
FROM empresas e
JOIN perfis p ON p.nome = 'ADMINISTRADOR'
WHERE e.cnpj = '00.000.000/0001-00';

/* Estados */
INSERT INTO estados (nome, sigla) VALUES
('ACRE', 'AC'),
('ALAGOAS', 'AL'),
('AMAPÁ', 'AP'),
('AMAZONAS', 'AM'),
('BAHIA', 'BA'),
('CEARÁ', 'CE'),
('DISTRITO FEDERAL', 'DF'),
('ESPÍRITO SANTO', 'ES'),
('GOIÁS', 'GO'),
('MARANHÃO', 'MA'),
('MATO GROSSO', 'MT'),
('MATO GROSSO DO SUL', 'MS'),
('MINAS GERAIS', 'MG'),
('PARÁ', 'PA'),
('PARAÍBA', 'PB'),
('PARANÁ', 'PR'),
('PERNAMBUCO', 'PE'),
('PIAUÍ', 'PI'),
('RIO DE JANEIRO', 'RJ'),
('RIO GRANDE DO NORTE', 'RN'),
('RIO GRANDE DO SUL', 'RS'),
('RONDÔNIA', 'RO'),
('RORAIMA', 'RR'),
('SANTA CATARINA', 'SC'),
('SÃO PAULO', 'SP'),
('SERGIPE', 'SE'),
('TOCANTINS', 'TO');

/* Localidades iniciais */
INSERT INTO cidades (nome, estado_id)
SELECT 'MINEIROS', id FROM estados WHERE sigla = 'GO'
UNION ALL
SELECT 'MONTIVIDIU', id FROM estados WHERE sigla = 'GO'
UNION ALL
SELECT 'SÃO PAULO', id FROM estados WHERE sigla = 'SP';

INSERT INTO bairros (nome, cidade_id)
SELECT 'CENTRO', id FROM cidades WHERE nome = 'MINEIROS'
UNION ALL
SELECT 'PARQUE AMAZONAS', id FROM cidades WHERE nome = 'MINEIROS'
UNION ALL
SELECT 'JARDIM AMÉRICA', id FROM cidades WHERE nome = 'MINEIROS';

/* Cadastros auxiliares */
INSERT INTO categorias_clientes (nome) VALUES
('ATACADO'),
('VAREJO'),
('MRO'),
('HOME CENTER'),
('CONSTRUTORA');

INSERT INTO subcategorias_clientes (categoria_id, nome)
SELECT id, 'MATERIAIS DE CONSTRUÇÃO' FROM categorias_clientes WHERE nome = 'ATACADO'
UNION ALL
SELECT id, 'MATERIAIS DE CONSTRUÇÃO' FROM categorias_clientes WHERE nome = 'VAREJO'
UNION ALL
SELECT id, 'FERRAGISTA' FROM categorias_clientes WHERE nome = 'VAREJO'
UNION ALL
SELECT id, 'FERRAMENTAS E PARAFUSOS' FROM categorias_clientes WHERE nome = 'VAREJO';

INSERT INTO departamentos_clientes (nome) VALUES
('FINANCEIRO'),
('COMERCIAL'),
('COMPRAS'),
('LOGÍSTICA');

INSERT INTO tipos_endereco (tipo) VALUES
('FATURAMENTO'),
('ENTREGA'),
('COBRANÇA');

INSERT INTO categorias_produtos (nome) VALUES
('FERRAMENTAS');

INSERT INTO subcategorias_produtos (categoria_id, nome)
SELECT id, 'FERRAMENTAS MANUAIS'
FROM categorias_produtos
WHERE nome = 'FERRAMENTAS';

INSERT INTO tipos_frete (nome) VALUES
('CIF'),
('FOB'),
('REDESPACHO');

INSERT INTO tipos_pedido (nome) VALUES
('COTAÇÃO'),
('PEDIDO'),
('BONIFICAÇÃO'),
('REPOSIÇÃO');

INSERT INTO status_pedido (nome, ordem_fluxo, finalizador) VALUES
('COTAÇÃO', 1, FALSE),
('EM ELABORAÇÃO', 2, FALSE),
('ENVIADO À REPRESENTADA', 3, FALSE),
('CONFIRMADO', 4, FALSE),
('FECHADO PERDIDO', 5, TRUE),
('FATURADO PARCIALMENTE', 6, FALSE),
('FATURADO TOTALMENTE', 7, FALSE),
('ENTREGUE', 8, TRUE),
('CANCELADO', 9, TRUE);

INSERT INTO status_comissao (nome) VALUES
('PREVISTA'),
('APURADA'),
('LIBERADA'),
('RECEBIDA'),
('CONTESTADA'),
('CANCELADA');

INSERT INTO status_visita (nome) VALUES
('AGENDADA'),
('EM ANDAMENTO'),
('REALIZADA'),
('CANCELADA'),
('NÃO REALIZADA');

COMMIT;
=======
SET NAMES utf8mb4;

INSERT INTO empresas (razao_social,nome_fantasia,cnpj,email,telefone,ativo)
VALUES ('SGV9 SISTEMA DE GESTAO DE VENDAS','SGV9',NULL,'admin@sgv9.com.br',NULL,1);

INSERT INTO perfis (nome,descricao) VALUES
('ADMINISTRADOR','ACESSO TOTAL AO SISTEMA'),
('ASSISTENTE','ACESSO OPERACIONAL'),
('VENDEDOR','ACESSO COMERCIAL'),
('FINANCEIRO','ACESSO FINANCEIRO'),
('CONSULTA','ACESSO SOMENTE PARA CONSULTA');

INSERT INTO permissoes (codigo,descricao) VALUES
('REPRESENTADAS_VISUALIZAR','VISUALIZAR REPRESENTADAS'),
('REPRESENTADAS_GERENCIAR','CADASTRAR E ALTERAR REPRESENTADAS'),
('LOCALIDADES_VISUALIZAR','VISUALIZAR LOCALIDADES'),
('LOCALIDADES_GERENCIAR','CADASTRAR LOCALIDADES'),
('CLIENTES_VISUALIZAR','VISUALIZAR CLIENTES'),
('PRODUTOS_VISUALIZAR','VISUALIZAR PRODUTOS'),
('PEDIDOS_VISUALIZAR','VISUALIZAR PEDIDOS'),
('COMISSOES_VISUALIZAR','VISUALIZAR COMISSOES'),
('USUARIOS_GERENCIAR','GERENCIAR USUARIOS');

INSERT INTO perfil_permissoes (perfil_id,permissao_id)
SELECT p.id, pe.id FROM perfis p CROSS JOIN permissoes pe WHERE p.nome='ADMINISTRADOR';

INSERT INTO perfil_permissoes (perfil_id,permissao_id)
SELECT p.id,pe.id FROM perfis p JOIN permissoes pe
ON pe.codigo IN ('REPRESENTADAS_VISUALIZAR','LOCALIDADES_VISUALIZAR','LOCALIDADES_GERENCIAR','CLIENTES_VISUALIZAR','PRODUTOS_VISUALIZAR','PEDIDOS_VISUALIZAR')
WHERE p.nome='ASSISTENTE';

INSERT INTO perfil_permissoes (perfil_id,permissao_id)
SELECT p.id,pe.id FROM perfis p JOIN permissoes pe
ON pe.codigo IN ('REPRESENTADAS_VISUALIZAR','LOCALIDADES_VISUALIZAR','CLIENTES_VISUALIZAR','PRODUTOS_VISUALIZAR','PEDIDOS_VISUALIZAR')
WHERE p.nome='VENDEDOR';

INSERT INTO perfil_permissoes (perfil_id,permissao_id)
SELECT p.id,pe.id FROM perfis p JOIN permissoes pe
ON pe.codigo IN ('CLIENTES_VISUALIZAR','PEDIDOS_VISUALIZAR','COMISSOES_VISUALIZAR')
WHERE p.nome='FINANCEIRO';

INSERT INTO perfil_permissoes (perfil_id,permissao_id)
SELECT p.id,pe.id FROM perfis p JOIN permissoes pe
ON pe.codigo IN ('REPRESENTADAS_VISUALIZAR','LOCALIDADES_VISUALIZAR','CLIENTES_VISUALIZAR','PRODUTOS_VISUALIZAR','PEDIDOS_VISUALIZAR','COMISSOES_VISUALIZAR')
WHERE p.nome='CONSULTA';

INSERT INTO usuarios (empresa_id,perfil_id,nome,email,senha,ativo)
SELECT e.id,p.id,'ADMINISTRADOR','admin@sgv9.com.br',
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',1
FROM empresas e JOIN perfis p ON p.nome='ADMINISTRADOR' WHERE e.nome_fantasia='SGV9';

INSERT INTO estados (nome,sigla) VALUES
('ACRE','AC'),('ALAGOAS','AL'),('AMAPA','AP'),('AMAZONAS','AM'),('BAHIA','BA'),
('CEARA','CE'),('DISTRITO FEDERAL','DF'),('ESPIRITO SANTO','ES'),('GOIAS','GO'),
('MARANHAO','MA'),('MATO GROSSO','MT'),('MATO GROSSO DO SUL','MS'),
('MINAS GERAIS','MG'),('PARA','PA'),('PARAIBA','PB'),('PARANA','PR'),
('PERNAMBUCO','PE'),('PIAUI','PI'),('RIO DE JANEIRO','RJ'),
('RIO GRANDE DO NORTE','RN'),('RIO GRANDE DO SUL','RS'),('RONDONIA','RO'),
('RORAIMA','RR'),('SANTA CATARINA','SC'),('SAO PAULO','SP'),
('SERGIPE','SE'),('TOCANTINS','TO');

INSERT INTO cidades (nome,estado_id)
SELECT 'MINEIROS',id FROM estados WHERE sigla='GO';
INSERT INTO cidades (nome,estado_id)
SELECT 'MONTIVIDIU',id FROM estados WHERE sigla='GO';
INSERT INTO cidades (nome,estado_id)
SELECT 'SAO PAULO',id FROM estados WHERE sigla='SP';

INSERT INTO bairros (nome,cidade_id)
SELECT 'CENTRO',id FROM cidades WHERE nome='MINEIROS';
INSERT INTO bairros (nome,cidade_id)
SELECT 'PARQUE AMAZONAS',id FROM cidades WHERE nome='MINEIROS';
INSERT INTO bairros (nome,cidade_id)
SELECT 'JARDIM AMERICA',id FROM cidades WHERE nome='MINEIROS';

INSERT INTO categorias(nome) VALUES ('ATACADO'),('VAREJO'),('MRO'),('HOME CENTER'),('CONSTRUTORA');
INSERT INTO subcategorias(nome,categoria_id)
SELECT 'MATERIAIS DE CONSTRUCAO',id FROM categorias WHERE nome='VAREJO';
INSERT INTO frete(frete) VALUES ('CIF'),('FOB'),('REDESPACHO');
INSERT INTO tipopedidos(tipo) VALUES ('PEDIDO'),('COTACAO');
INSERT INTO statuspedidos(status) VALUES ('ABERTO'),('FECHADO GANHO'),('FECHADO PERDIDO'),('FATURADO TOTAL'),('FATURADO PARCIAL');
>>>>>>> 8ea7486 (chore: Correções)
