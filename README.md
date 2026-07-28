# SGV9

![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1?logo=mysql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green)

Sistema de Gestão de Vendas (SGV9)

O SGV9 é um sistema de gestão comercial desenvolvido para controlar clientes, representadas, produtos, pedidos, vendedores e demais processos comerciais de forma organizada, segura e escalável.

O projeto foi desenvolvido utilizando PHP, MySQL e Docker, seguindo uma arquitetura modular que facilita sua evolução.

---

# Objetivos

- Centralizar a gestão comercial.
- Facilitar o cadastro de clientes e representadas.
- Gerenciar pedidos de vendas.
- Controlar usuários e permissões.
- Possibilitar crescimento modular do sistema.
- Manter uma arquitetura organizada e de fácil manutenção.

---

# Tecnologias

- PHP 8.2+
- MySQL 8.4
- Docker
- Docker Compose
- Nginx
- HTML5
- CSS3
- JavaScript

---

# Estrutura do Projeto

```text
sgv9_web
│
├── backend/               Aplicação PHP
├── database/
│   └── migrations/        Evolução do banco de dados
├── docs/                  Documentação do projeto
├── init-scripts/          Criação inicial do banco
├── nginx/
├── Dockerfile
├── docker-compose.yml
└── README.md
```

---

# Banco de Dados

O projeto possui duas estratégias para gerenciamento do banco.

## Banco novo

Quando o banco é criado pela primeira vez, o MySQL executa automaticamente:

```text
init-scripts/
    01-ddl.sql
    02-dml.sql
```

O primeiro cria toda a estrutura.

O segundo realiza a carga inicial dos dados.

---

## Banco existente

Toda alteração estrutural deverá ser realizada através de migrations.

```text
database/
└── migrations/
```

Exemplo:

```text
003-localidades-permissoes.sql

004-localidades-editaveis.sql
```

Dessa forma não é necessário recriar o banco de dados.

---

# Instalação

Clone o projeto

```bash
git clone <repositorio>
```

Entre na pasta

```bash
cd sgv9_web
```

Suba os containers

```bash
docker compose up -d --build
```

Acesse

```
http://localhost
```

---

# Desenvolvimento

O projeto segue um fluxo baseado em Git Flow simplificado.

Branches principais

```text
main
```

Versões estáveis.

```text
develop
```

Integração do desenvolvimento.

```text
feature/*
```

Novas funcionalidades.

```text
bugfix/*
```

Correções.

```text
hotfix/*
```

Correções urgentes.

---

# Fluxo de Desenvolvimento

Criar uma nova funcionalidade

```bash
git checkout develop

git pull origin develop

git checkout -b feature/nome-da-feature
```

Realizar o desenvolvimento.

Executar testes.

Enviar para o GitHub.

```bash
git push -u origin feature/nome-da-feature
```

Criar um Pull Request para:

```text
develop
```

Após aprovação:

Merge da feature.

---

# Convenção de Commits

Utilizamos o padrão Conventional Commits.

Exemplos

```text
feat(localidades): permitir edição de localidades

fix(login): corrigir expiração de sessão

docs(api): atualizar documentação

refactor(database): reorganizar migrations

chore(docker): atualizar compose
```

---

# Documentação

Toda documentação está localizada em:

```text
docs/
```

Arquivos principais

```text
README.md

api.md

banco-de-dados.md

changelog.md

padrao-codigo.md

roadmap.md

requisitos/
```

---

# Arquitetura

O sistema foi dividido em módulos independentes.

Exemplo

```text
backend/

localidades/

usuarios/

representadas/
```

Cada módulo deverá possuir suas próprias regras de negócio, telas e persistência de dados.

---

# Decisões Arquiteturais

As principais decisões técnicas do projeto são registradas na documentação do diretório `docs/`.

Essas decisões orientam a evolução do SGV9 e garantem consistência entre as versões.

Algumas decisões adotadas atualmente:

- Utilizar `init-scripts/` apenas para criação de um banco novo.
- Utilizar `database/migrations/` para evolução de bancos existentes.
- Toda alteração estrutural do banco deve possuir uma migration correspondente.
- O fluxo de desenvolvimento segue o modelo `feature → develop → main`.
- Commits devem representar uma única intenção (feature, correção, refatoração ou documentação).

---

# Organização do Banco

Estrutura inicial

```text
init-scripts/
```

Migrações

```text
database/migrations/
```

Nunca alterar um banco existente diretamente.

Toda alteração deverá possuir uma migration correspondente.

---

# Roadmap

## Versão 9.1

- Cadastro de Usuários
- Representadas
- Localidades
- Permissões

## Versão 9.2

- Clientes
- Produtos
- Pedidos

## Versão 9.3

- Financeiro
- Comissão
- Dashboard

## Versão 9.4

- Relatórios
- Indicadores
- Integrações

---

# Padrões do Projeto

- Não excluir registros históricos quando houver relacionamento.
- Utilizar migrations para alterações no banco.
- Commits pequenos e objetivos.
- Toda feature deverá possuir documentação.
- Todo código deverá passar por revisão antes do merge.

---

# Licença

Projeto desenvolvido para fins comerciais e de estudo.

© SGV9 - Sistema de Gestão de Vendas