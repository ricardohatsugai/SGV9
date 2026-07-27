<div align="center">

# SGV9

### Sistema de Gestão de Vendas

**ERP comercial para escritórios de representação, desenvolvido com foco em organização, segurança, desempenho e evolução contínua.**

![Status](https://img.shields.io/badge/status-Em%20Desenvolvimento-blue)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker)
![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.4%20LTS-4479A1?logo=mysql)
![Nginx](https://img.shields.io/badge/Nginx-Alpine-009639?logo=nginx)
![Git](https://img.shields.io/badge/Git-Versionado-F05032?logo=git)
![License](https://img.shields.io/badge/license-MIT-green)

</div>

---

# Sobre o projeto

O **SGV9** é um Sistema de Gestão de Vendas voltado para escritórios de representação comercial que trabalham com múltiplos vendedores, clientes, produtos e empresas representadas.

O sistema foi concebido como um ERP comercial enxuto, com foco no acompanhamento de todo o ciclo de vendas:

```text
Cliente
   ↓
Cotação
   ↓
Pedido
   ↓
Envio à representada
   ↓
Confirmação
   ↓
Faturamento parcial ou total
   ↓
Entrega
   ↓
Apuração da comissão
```

A emissão da nota fiscal é realizada pela representada diretamente ao cliente. O SGV9 será responsável pelo registro e acompanhamento das informações comerciais, do faturamento e das comissões.

Além de ser um sistema funcional, o projeto também atua como um laboratório de estudos em Engenharia de Software, Banco de Dados, Segurança, Docker, Git e desenvolvimento de sistemas corporativos.

O SGV9 nasceu da evolução do **SGV8**, sendo reestruturado para eliminar limitações anteriores e adotar uma base mais organizada, segura e preparada para crescimento.

---

# Objetivos

* Gerenciar escritórios de representação comercial
* Controlar usuários, perfis e permissões
* Gerenciar vendedores
* Cadastrar representadas e seus contatos
* Cadastrar clientes, contatos e endereços
* Consultar dados cadastrais por CNPJ
* Gerenciar produtos de múltiplas representadas
* Importar produtos por cadastro manual, Excel, CSV ou API
* Controlar tabelas de preços com vigência
* Aplicar políticas comerciais por cliente
* Controlar promoções e descontos
* Criar cotações e pedidos
* Acompanhar faturamentos parciais e totais
* Controlar comissões por representada, vendedor, produto e pedido
* Gerenciar agenda, roteiros e visitas comerciais
* Registrar logs de operações
* Gerar dashboards e relatórios gerenciais
* Evoluir o projeto de forma incremental e versionada

---

# Arquitetura atual

```text
                     Navegador
                         │
                         ▼
                      Nginx
                         │
                         ▼
                    PHP-FPM
                         │
                         ▼
              Regras da aplicação
                         │
                         ▼
                 PDO / MySQL
                         │
                         ▼
                    MySQL 8.4
                         │
                         ▼
                      Docker
```

A arquitetura atual foi mantida simples e modular para facilitar o aprendizado e a evolução gradual do projeto.

Cada funcionalidade principal possui seu próprio diretório dentro do `backend`, enquanto componentes compartilhados ficam centralizados em pastas como `includes`, `config` e `assets`.

---

# Tecnologias

## Backend

* PHP 8.x
* PDO
* Sessões PHP
* Autenticação com `password_hash` e `password_verify`
* Proteção CSRF
* Controle de acesso baseado em permissões

## Frontend

* HTML5
* CSS3
* JavaScript
* Layout responsivo
* Máscaras e validações no navegador

## Banco de Dados

* MySQL 8.4 LTS
* InnoDB
* Chaves estrangeiras
* Restrições de integridade
* Índices de desempenho
* Campos JSON para logs e integrações

## Infraestrutura

* Docker
* Docker Compose
* Nginx Alpine
* PHP-FPM
* phpMyAdmin
* Linux Ubuntu / WSL2

## Controle de versão

* Git
* GitHub
* Branch principal de desenvolvimento: `develop`
* Branches de funcionalidades: `feature/*`
* Branches de correção: `fix/*`
* Branches de versão: `release/*`
* Conventional Commits

## Tecnologias planejadas

As tecnologias abaixo fazem parte da visão futura do projeto, mas ainda não compõem a aplicação atual:

* APIs REST
* Testes automatizados
* CI/CD
* Java 21
* Spring Boot 3
* PostgreSQL
* Microsserviços

---

# Estrutura do projeto

```text
~/sgv9_web
│
├── backend/
│   ├── assets/
│   │   ├── css/
│   │   └── js/
│   │
│   ├── config/
│   ├── database/
│   ├── includes/
│   ├── localidades/
│   ├── representadas/
│   ├── usuarios/
│   │
│   ├── 403.php
│   ├── auth.php
│   ├── dashboard.php
│   ├── index.php
│   ├── login.php
│   └── logout.php
│
├── docker/
├── docs/
├── logs/
├── init-scripts/
├── nginx/
│
├── .env
├── .env.example
├── .gitignore
├── docker-compose.yml
├── Dockerfile
├── LICENSE
└── README.md
```

## Diretórios principais

### `backend/`

Contém o código PHP, as telas, validações, autenticação, permissões e módulos do sistema.

### `backend/assets/`

Arquivos públicos utilizados pela interface:

* CSS
* JavaScript

### `backend/config/`

Configurações centrais da aplicação, como conexão com o banco de dados e sessões.

### `backend/database/`

Scripts SQL complementares e atualizações incrementais do banco.

### `backend/includes/`

Componentes reutilizáveis:

* layout administrativo
* permissões
* mensagens temporárias
* validadores
* funções auxiliares

### `backend/localidades/`

Cadastro e consulta de:

* estados
* cidades
* bairros

### `backend/representadas/`

Cadastro, edição, pesquisa e ativação de empresas representadas.

### `backend/usuarios/`

Cadastro e manutenção de usuários e seus níveis de acesso.

### `docker/`

Arquivos auxiliares relacionados à infraestrutura dos containers.

### `docs/`

Documentação técnica, decisões de arquitetura, roadmap e histórico do projeto.

### `logs/`

Diretório reservado para logs da aplicação e dos serviços.

---

# Banco de dados

O banco de dados foi estruturado para suportar:

* escritórios com vários representantes
* múltiplos usuários
* perfis e permissões
* múltiplas representadas
* clientes, contatos e endereços
* vendedores
* produtos e categorias
* SKU do cliente
* tabelas de preços com vigência
* políticas comerciais
* promoções
* pedidos e itens
* movimentações de pedidos
* faturamentos parciais e totais
* regras de comissão
* comissões previstas e recebidas
* agenda e visitas
* roteiros comerciais
* importações
* consultas de CNPJ
* logs de usuários

O projeto utiliza:

* chaves primárias
* chaves estrangeiras
* restrições únicas
* validações com `CHECK`
* índices de desempenho
* exclusão restritiva ou em cascata conforme a regra de negócio

---

# Funcionalidades implementadas

## Autenticação

* [x] Tela de login responsiva
* [x] Autenticação com e-mail e senha
* [x] Senhas armazenadas com hash
* [x] Sessão segura
* [x] Regeneração do identificador de sessão
* [x] Proteção CSRF
* [x] Logout
* [x] Expiração por inatividade
* [x] Bloqueio de usuário inativo
* [x] Bloqueio de empresa inativa
* [x] Registro do último acesso
* [x] Registro de login em log

## Administração

* [x] Dashboard inicial
* [x] Layout administrativo responsivo
* [x] Menu lateral
* [x] Exibição do menu conforme permissões
* [x] Controle de acesso por perfil
* [x] Página de acesso negado
* [x] Mensagens de sucesso e erro

## Usuários

* [x] Cadastro de usuários
* [x] Edição de usuários
* [x] Ativação e inativação
* [x] Associação de perfil
* [x] Perfis iniciais:

  * Administrador
  * Vendedor
  * Assistente
  * Financeiro
  * Consulta

## Representadas

* [x] Cadastro de representadas
* [x] Edição de representadas
* [x] Pesquisa por nome ou CNPJ
* [x] Ativação e inativação
* [x] Validação matemática de CNPJ no navegador
* [x] Validação matemática de CNPJ no servidor
* [x] Máscara de CNPJ
* [x] Máscara de CEP
* [x] Registro de inclusão e alteração em log

## Localidades

* [x] Cadastro de estados
* [x] Cadastro de cidades
* [x] Cadastro de bairros
* [x] Listagem de localidades
* [x] Controle de duplicidade
* [x] Permissões específicas
* [x] Atalho para cadastrar bairro na tela de representadas
* [x] Retorno automático ao cadastro da representada
* [x] Seleção automática do bairro recém-cadastrado

---

# Roadmap

## Fase 1 — Infraestrutura e banco de dados

* [x] Estrutura inicial do projeto
* [x] Docker
* [x] Docker Compose
* [x] MySQL 8.4 LTS
* [x] phpMyAdmin
* [x] Nginx
* [x] PHP-FPM
* [x] Scripts DDL
* [x] Scripts DML
* [x] Estrutura inicial do banco
* [x] Diretórios `docker`, `docs` e `logs`

---

## Fase 2 — Autenticação e administração

* [x] Tela de login
* [x] Controle de sessão
* [x] Logout
* [x] Perfis
* [x] Permissões
* [x] Cadastro de usuários
* [x] Dashboard inicial
* [x] Layout administrativo
* [x] Logs de autenticação

---

## Fase 3 — Cadastros básicos

* [x] Cadastro de representadas
* [x] Validação de CNPJ
* [x] Cadastro de estados
* [x] Cadastro de cidades
* [x] Cadastro de bairros
* [ ] Contatos das representadas
* [ ] Cadastro de vendedores
* [ ] Cadastro de transportadoras

---

## Fase 4 — Clientes

* [ ] Cadastro de clientes
* [ ] Consulta automática por CNPJ
* [ ] Contatos dos clientes
* [ ] Endereços de faturamento
* [ ] Endereços de entrega
* [ ] Endereços de cobrança
* [ ] Categorias e subcategorias
* [ ] Políticas comerciais por cliente

---

## Fase 5 — Produtos e preços

* [ ] Cadastro de produtos
* [ ] Categorias de produtos
* [ ] Subcategorias de produtos
* [ ] SKU da representada
* [ ] SKU do cliente
* [ ] Importação manual
* [ ] Importação por Excel
* [ ] Importação por CSV
* [ ] Importação por API
* [ ] Tabelas de preços
* [ ] Vigência de preços
* [ ] Promoções
* [ ] Políticas de desconto

---

## Fase 6 — Pedidos

* [ ] Cotações
* [ ] Criação de pedidos
* [ ] Inclusão de itens
* [ ] Aplicação da tabela de preços
* [ ] Aplicação da política comercial
* [ ] Aplicação de promoções
* [ ] Desconto adicional
* [ ] Cálculo de IPI
* [ ] Totalização
* [ ] Workflow do pedido
* [ ] Histórico de movimentações
* [ ] Envio à representada

---

## Fase 7 — Faturamento e comissões

* [ ] Registro de notas fiscais
* [ ] Faturamento parcial
* [ ] Faturamento total
* [ ] Previsão de entrega
* [ ] Confirmação de entrega
* [ ] Regras de comissão
* [ ] Comissão por representada
* [ ] Comissão por vendedor
* [ ] Comissão por produto
* [ ] Comissão por pedido
* [ ] Comissão com percentual variável
* [ ] Controle de comissão recebida

---

## Fase 8 — Agenda comercial

* [ ] Agenda
* [ ] Roteiros de visitas
* [ ] Check-in
* [ ] Check-out
* [ ] Geolocalização
* [ ] Objetivo da visita
* [ ] Resultado da visita
* [ ] Relatórios de visitas

---

## Fase 9 — Qualidade e integrações

* [ ] Testes automatizados
* [ ] API REST
* [ ] Documentação da API
* [ ] CI/CD
* [ ] Monitoramento
* [ ] Rotina de backup
* [ ] Auditoria ampliada
* [ ] Avaliação de integração com Java e Spring Boot
* [ ] Avaliação de uso do PostgreSQL

---

## Versão 1.0

* [ ] Primeira versão estável do SGV9
* [ ] Cadastros principais concluídos
* [ ] Fluxo completo de pedidos
* [ ] Acompanhamento de faturamento
* [ ] Controle de comissões
* [ ] Agenda comercial
* [ ] Documentação de instalação e uso

---

# Controle de versões

O projeto utiliza uma estratégia de branches baseada em desenvolvimento contínuo.

```text
main
```

Contém versões estáveis e prontas para uso.

```text
develop
```

Branch de integração e desenvolvimento do projeto.

```text
feature/*
```

Utilizada para novas funcionalidades.

Exemplos:

```text
feature/clientes
feature/produtos
feature/pedidos
feature/comissoes
```

```text
fix/*
```

Utilizada para correções de erros.

```text
release/*
```

Utilizada para preparação de versões.

```text
hotfix/*
```

Utilizada para correções urgentes em produção.

## Padrão de commits

O projeto utiliza o padrão Conventional Commits.

Exemplos:

```bash
git commit -m "chore: padroniza estrutura inicial do SGV9"
git commit -m "feat: implementa cadastro de representadas"
git commit -m "feat: adiciona cadastro de localidades"
git commit -m "fix: corrige validação de CNPJ"
git commit -m "docs: atualiza documentação do projeto"
git commit -m "refactor: reorganiza validadores compartilhados"
```

Tipos principais:

| Tipo       | Utilização                                |
| ---------- | ----------------------------------------- |
| `feat`     | Nova funcionalidade                       |
| `fix`      | Correção de erro                          |
| `docs`     | Alteração de documentação                 |
| `refactor` | Reorganização sem mudança funcional       |
| `test`     | Inclusão ou alteração de testes           |
| `style`    | Formatação sem alteração de lógica        |
| `chore`    | Infraestrutura, configuração e manutenção |

---

# Filosofia do projeto

O SGV9 não tem como objetivo apenas gerar um sistema funcional.

Seu propósito é construir uma solução sólida, segura, organizada e de fácil manutenção, aplicando progressivamente princípios e práticas como:

* Clean Code
* Separação de responsabilidades
* Código reutilizável
* Validação no cliente e no servidor
* Segurança por padrão
* Controle de acesso por permissões
* Integridade de dados
* Boas práticas SQL
* Versionamento semântico
* Conventional Commits
* Desenvolvimento incremental
* Documentação contínua
* Testes automatizados
* SOLID
* Design Patterns
* Clean Architecture
* Domain-Driven Design

Alguns desses conceitos já estão presentes no projeto, enquanto outros serão incorporados gradualmente conforme a aplicação evoluir.

---

# Evolução

```text
SGV8
Projeto legado
    │
    ▼
Experiência adquirida
    │
    ▼
SGV9
Nova modelagem de dados
Docker e containers
Autenticação segura
Perfis e permissões
Arquitetura modular
Desenvolvimento versionado
    │
    ▼
SGV9 v1.0
ERP comercial estável
```

---

# Ambiente de desenvolvimento

| Tecnologia     | Versão ou situação |
| -------------- | ------------------ |
| Ubuntu / WSL2  | Atual              |
| Docker Desktop | Atual              |
| Docker Compose | Atual              |
| PHP            | 8.x                |
| MySQL          | 8.4 LTS            |
| Nginx          | Alpine             |
| phpMyAdmin     | Atual              |
| Git            | Atual              |
| Java           | 21 — planejado     |
| Spring Boot    | 3.x — planejado    |
| PostgreSQL     | 16 — planejado     |

---

# Como executar

Clone o repositório:

```bash
git clone https://github.com/ricardohatsugai/sgv9.git
```

Entre na pasta do projeto:

```bash
cd sgv9
```

Crie o arquivo de ambiente:

```bash
cp .env.example .env
```

Revise as credenciais e portas configuradas no `.env`.

Suba os containers:

```bash
docker compose up -d --build
```

Acompanhe a inicialização:

```bash
docker compose logs -f
```

## Acessos

Sistema:

```text
http://localhost
```

phpMyAdmin:

```text
http://localhost:8080
```

As portas podem variar conforme os valores definidos no arquivo `.env`.

---

# Comandos úteis

Subir os containers:

```bash
docker compose up -d
```

Reconstruir os serviços:

```bash
docker compose up -d --build
```

Verificar os containers:

```bash
docker compose ps
```

Visualizar os logs:

```bash
docker compose logs -f
```

Parar os containers:

```bash
docker compose down
```

Parar os containers e remover o volume do banco:

```bash
docker compose down -v
```

> O comando com `-v` remove os dados persistidos do MySQL e deve ser utilizado com cuidado.

---

# Segurança

O SGV9 já utiliza algumas práticas importantes:

* hash seguro de senhas
* consultas preparadas com PDO
* proteção contra SQL Injection
* proteção CSRF
* cookies de sessão com `HttpOnly`
* política `SameSite`
* regeneração do identificador de sessão
* expiração por inatividade
* controle por perfis e permissões
* validação de dados no navegador e no servidor
* logs de autenticação e alterações
* bloqueio de usuários inativos

Antes do uso em produção, ainda serão necessários:

* HTTPS
* política de senhas
* recuperação segura de senha
* bloqueio por tentativas de login
* backups automatizados
* gerenciamento seguro de segredos
* revisão de permissões dos containers
* auditoria de dependências
* testes de segurança

---

# Autor

**Ricardo Hatsugai**

Representante Comercial • Desenvolvedor de Software

LinkedIn:
https://linkedin.com/in/ricardohatsugai

GitHub:
https://github.com/ricardohatsugai

---

# Licença

Este projeto está licenciado sob a licença MIT.

Consulte o arquivo `LICENSE` para mais informações.

---

> “Grandes sistemas não nascem grandes. Eles evoluem a partir de uma arquitetura sólida.”
