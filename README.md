<div align="center">

# SGV9
### Sistema de Gestão de Vendas

**Um projeto moderno para gestão comercial, desenvolvido com foco em arquitetura, desempenho e escalabilidade.**

![Status](https://img.shields.io/badge/status-Em%20Desenvolvimento-blue)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker)
![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php)
![Java](https://img.shields.io/badge/Java-21-ED8B00?logo=openjdk)
![Spring Boot](https://img.shields.io/badge/Spring%20Boot-3.x-6DB33F?logo=springboot)
![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1?logo=mysql)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql)
![License](https://img.shields.io/badge/license-MIT-green)

</div>

---

# Sobre o projeto

O **SGV9** é um Sistema de Gestão de Vendas (ERP Comercial) desenvolvido como um projeto de longo prazo, visando aplicar conceitos modernos de Engenharia de Software, Arquitetura de Sistemas e Banco de Dados.

Mais do que um sistema comercial, o SGV9 é também um laboratório de estudos, onde novas tecnologias e boas práticas são incorporadas continuamente.

O projeto nasceu da evolução do **SGV8**, reescrito praticamente do zero para eliminar limitações arquiteturais e adotar uma base mais moderna e escalável.

---

# Objetivos

- Gestão completa de clientes
- Cadastro de representadas
- Controle de vendedores
- Gestão de produtos
- Controle de pedidos
- Histórico comercial
- Relatórios gerenciais
- Dashboard de indicadores
- APIs REST
- Arquitetura desacoplada
- Banco de dados escalável
- Containers Docker

---

# Arquitetura

```
                    Cliente

                        │

            PHP / Java / APIs REST

                        │

             Regras de Negócio

                        │

            Camada de Persistência

                        │

          MySQL / PostgreSQL

                        │

                  Docker
```

---

# Tecnologias

## Backend

- PHP 8
- Java
- Spring Boot

## Banco de Dados

- MySQL 8.4 LTS
- PostgreSQL

## Infraestrutura

- Docker
- Docker Compose
- Nginx
- Linux (Ubuntu / WSL2)

## Ferramentas

- Git
- GitHub
- VS Code

---

# Estrutura do Projeto

```
SGV9

├── backend/
├── docker/
├── init-scripts/
├── nginx/
├── docs/
├── docker-compose.yml
├── .env.example
├── README.md
└── LICENSE
```

---

# Roadmap

## Fase 1

- [x] Estrutura inicial
- [x] Docker
- [x] MySQL
- [x] PHPMyAdmin
- [ ] Configuração do Nginx
- [ ] Organização do Backend

---

## Fase 2

- [ ] Autenticação
- [ ] Cadastro de Usuários
- [ ] Controle de Clientes
- [ ] Cadastro de Produtos
- [ ] Representadas

---

## Fase 3

- [ ] Pedidos
- [ ] Financeiro
- [ ] Dashboard
- [ ] API REST

---

## Fase 4

- [ ] Spring Boot
- [ ] Microsserviços
- [ ] Testes Automatizados
- [ ] CI/CD

---

# Filosofia do Projeto

O SGV9 não tem como objetivo apenas gerar um sistema funcional.

Seu propósito é construir uma base sólida, limpa e de fácil manutenção, aplicando continuamente princípios como:

- SOLID
- Clean Architecture
- Clean Code
- Design Patterns
- Domain Driven Design (DDD)
- Boas práticas SQL
- Segurança
- Escalabilidade

---

# Evolução

```
SGV8 (Projeto Legado)
        │
        ▼
Experiência adquirida
        │
        ▼
SGV9
Arquitetura moderna
Banco de dados robusto
Containers Docker
APIs REST
Escalabilidade
```

---

# Ambiente de Desenvolvimento

| Tecnologia | Versão |
|------------|---------|
| Ubuntu (WSL2) | Atual |
| Docker Desktop | Atual |
| Docker Compose | Atual |
| PHP | 8.x |
| Java | 21 |
| Spring Boot | 3.x |
| MySQL | 8.4 LTS |
| PostgreSQL | 16 |
| Nginx | Alpine |

---

# Como executar

```bash
git clone https://github.com/ricardohatsugai/sgv9.git

cd sgv9

cp .env.example .env

docker compose up -d --build
```

Acesse:

```
Sistema:
http://localhost

phpMyAdmin:
http://localhost:8080
```

---

# Autor

**Ricardo Hatsugai**

Representante Comercial • Desenvolvedor de Software

LinkedIn:
https://linkedin.com/in/ricardohatsugai

GitHub:
https://github.com/ricardohatsugai

---

> "Grandes sistemas não nascem grandes. Eles evoluem a partir de uma arquitetura sólida."

