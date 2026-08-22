# AGENTS.md — SGV9

## 1. Finalidade

Este arquivo define instruções operacionais para agentes de programação que trabalhem no repositório do **SGV9 — Sistema de Gestão de Vendas**.

O objetivo é garantir que alterações realizadas por agentes sejam incrementais, seguras, revisáveis, testáveis, reversíveis e coerentes com a arquitetura existente.

## 2. Regra fundamental: o projeto é a fonte de verdade

Antes de propor ou implementar alterações relevantes, inspecione o estado atual do repositório.

Nunca presuma a existência de arquivos, classes, tabelas, colunas, funções, rotas, APIs, diretórios, migrations, permissões ou componentes sem evidência no projeto ou na documentação versionada.

Prioridade das fontes:

1. código atual do repositório;
2. schema/export atual do banco;
3. documentação versionada;
4. decisões recentes registradas no projeto;
5. conhecimento histórico.

Quando documentação histórica contradizer o código atual, investigue a divergência e trate a implementação atual como evidência principal do estado do sistema.

## 3. Stack oficial atual

Considere como stack principal, salvo evidência atual em contrário:

- PHP 8.x;
- MySQL 8.4 LTS;
- Docker e Docker Compose no desenvolvimento;
- WSL2/Ubuntu;
- Windows 11 como host;
- phpMyAdmin para administração do banco;
- Git e GitHub para versionamento.

Java e Spring Boot são possibilidades futuras e **não devem ser introduzidos automaticamente**.

Delphi e PostgreSQL pertencem ao histórico do projeto e não devem ser adotados em novas funcionalidades sem decisão arquitetural explícita.

## 4. Ambiente e inspeção inicial

O repositório historicamente é utilizado no WSL2, normalmente em `~/sgv9_web`, mas confirme o ambiente atual.

Antes de alterações importantes, execute ou verifique, quando aplicável:

```bash
git status
git branch --show-current
```

Para inspecionar o ambiente Docker:

```bash
docker ps
docker compose ps
```

Não presuma nomes de containers com base em estados históricos.

Antes de fornecer ou executar comandos dependentes de um container específico, descubra o nome atual.

## 5. Política de alterações

Prefira alterações:

- incrementais;
- pequenas quando possível;
- diretamente relacionadas ao objetivo solicitado;
- revisáveis;
- testáveis;
- reversíveis;
- documentadas quando relevante;
- versionadas.

Evite refatorações oportunistas.

Não transforme uma correção localizada em uma reconstrução do sistema.

Se identificar um problema não bloqueador fora do escopo, registre-o separadamente como recomendação futura.

Antes de modificar vários arquivos, verifique se o problema pode ser resolvido em uma área menor.

## 6. Processo para implementar mudanças

Para alterações significativas, siga esta ordem:

1. identificar o estado atual;
2. definir exatamente o problema;
3. localizar os arquivos e estruturas realmente envolvidos;
4. determinar a menor mudança correta;
5. avaliar impacto no banco;
6. avaliar autenticação, autorização e segurança;
7. avaliar regressões;
8. implementar;
9. validar;
10. revisar o diff;
11. documentar quando necessário;
12. preparar commit/PR.

Não considere uma alteração concluída apenas porque o caso principal passou a funcionar.

## 7. Segurança da aplicação

Funcionalidades web devem considerar, conforme aplicável:

- autenticação;
- autorização;
- CSRF;
- validação de entrada no servidor;
- escaping de saída;
- SQL Injection;
- XSS;
- controle de sessão;
- manipulação de IDs;
- exposição de erros;
- operações indevidas por usuários sem permissão.

Nunca remova controles essenciais de segurança apenas para fazer uma funcionalidade funcionar.

Corrija a causa do problema.

### SQL

Utilize prepared statements.

Nunca monte SQL concatenando diretamente entrada externa.

### XSS

Dados potencialmente controlados pelo usuário devem receber escaping adequado no contexto de saída.

Em HTML, quando compatível com o padrão existente:

```php
htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
```

Não considere dados armazenados no banco automaticamente seguros.

### CSRF

Operações que alteram estado — como criar, editar, excluir, ativar ou inativar — devem respeitar o mecanismo CSRF existente.

### IDs e relacionamentos

Para IDs recebidos via GET, POST, JSON ou URL, valide:

- formato/tipo;
- existência do registro;
- relacionamento com o contexto correto;
- autorização do usuário;
- consistência da operação.

## 8. Banco de dados e migrations

O banco principal é MySQL 8.4, salvo evidência atual em contrário.

Alterações estruturais devem preferencialmente utilizar migrations versionadas.

Evite alterações manuais e não documentadas diretamente no banco.

Antes de criar ou modificar estruturas, inspecione o schema atual.

Ao escrever migrations, considere:

- dados existentes;
- execução em ambientes diferentes;
- índices existentes;
- chaves estrangeiras;
- constraints;
- duplicidades;
- possibilidade de parte da alteração já existir;
- rollback quando aplicável;
- preservação de dados.

Antes de criar índice, coluna ou constraint, confirme que não existe.

Priorize integridade no banco quando apropriado, usando corretamente:

- PRIMARY KEY;
- FOREIGN KEY;
- UNIQUE;
- NOT NULL;
- índices;
- constraints.

Validação em PHP não substitui integridade no banco.

Quando uma regra de negócio puder sofrer condição de corrida, avalie proteção também no banco.

## 9. Transações

Utilize transações quando uma operação composta puder deixar dados parcialmente inconsistentes.

Não utilize transações mecanicamente quando não houver benefício de consistência.

## 10. Exclusão e status

Quando o modelo existente utilizar `ativo`, preserve o padrão de ativação/inativação quando apropriado.

Não substitua automaticamente esse comportamento por `DELETE` físico.

Antes de exclusões definitivas, avalie:

- foreign keys;
- histórico;
- auditoria;
- relatórios;
- dependências existentes e futuras.

## 11. Duplicidades

Quando relevante, proteja regras de unicidade em duas camadas:

- aplicação, para mensagem adequada ao usuário;
- banco, para integridade.

Confirme a regra de domínio antes de criar uma constraint.

## 12. PHP

Priorize:

- clareza;
- funções pequenas;
- responsabilidade definida;
- nomes descritivos;
- redução de duplicação;
- tratamento consistente de erros;
- padrões já existentes no SGV9.

Não introduza frameworks, bibliotecas ou dependências externas apenas por preferência tecnológica.

Antes de adicionar dependência, avalie necessidade, maturidade, manutenção, licença, compatibilidade e impacto no deploy.

Quando recursos nativos forem suficientes, prefira-os.

## 13. Compatibilidade de hospedagem

O SGV9 possui intenção de funcionamento em hospedagem compartilhada.

Não presuma que produção terá Docker ou o mesmo ambiente do desenvolvimento.

Ao tomar decisões de arquitetura ou dependências, considere:

- versão de PHP disponível;
- extensões;
- Apache/configuração do servidor;
- acesso a shell;
- cron;
- banco disponível;
- permissões de filesystem;
- limitações da hospedagem.

## 14. Docker e operações destrutivas

Docker é utilizado principalmente para padronizar o ambiente local.

Comandos como estes podem ser úteis para diagnóstico:

```bash
docker ps
docker compose ps
docker compose logs
```

Trate como alto risco operações como:

```text
rm -rf
git reset --hard
git clean -fd
git push --force
docker compose down -v
DROP DATABASE
DROP TABLE
TRUNCATE
```

Não execute nem recomende essas operações como primeira tentativa.

Antes de qualquer operação potencialmente destrutiva:

1. explique o impacto;
2. verifique alternativa não destrutiva;
3. preserve alterações/dados importantes;
4. recomende ou confirme backup quando houver risco de perda.

`docker compose down -v` pode remover dados persistentes e exige cuidado explícito.

## 15. Git

Fluxo historicamente preferido:

```text
develop
  ↓
feature/*
  ↓
Pull Request
  ↓
develop
```

Confirme a convenção atual antes de assumir que continua válida.

Antes de alterações importantes:

```bash
git status
git branch --show-current
```

Antes de commit:

```bash
git diff
git diff --check
```

Quando útil:

```bash
git diff --stat
```

Revise também arquivos untracked e possíveis segredos.

Não recomende `git push --force` como solução comum.

Quando houver divergência entre local e remoto, investigue o histórico antes de operações destrutivas.

Commits devem representar unidades lógicas e seguir a convenção existente.

Exemplos históricos de estilo:

```text
feat(localidades): permite editar estados, cidades e bairros
fix(localidades): corrige validação de duplicidade de bairros
refactor(auth): centraliza validação de permissões
docs(localidades): documenta edição e alteração de status
```

## 16. Segredos e credenciais

Nunca publique ou inclua em commits:

- senhas;
- tokens;
- chaves privadas;
- access keys;
- `.env`;
- credenciais de banco;
- segredos de produção.

Ao revisar diffs, verifique vazamentos acidentais.

Arquivos sensíveis devem estar protegidos pelo `.gitignore` quando apropriado.

## 17. Diagnóstico de bugs

Evite alterações às cegas.

Use preferencialmente:

```text
Sintoma
↓
Reprodução
↓
Camada provável
↓
Evidência
↓
Causa
↓
Correção mínima
↓
Teste
↓
Regressão
```

Analise literalmente logs, mensagens de erro, saídas de terminal, warnings, códigos de erro, conflitos e mensagens do MySQL/Docker.

Diferencie erro, warning e informação.

Não presuma que um comando funcionou sem evidência de sua saída ou resultado.

## 18. Validação após alterações

Após modificar PHP, execute lint nos arquivos relevantes, por exemplo:

```bash
php -l caminho/do/arquivo.php
```

Se o PHP estiver disponível apenas no container, primeiro identifique o container atual e então execute o equivalente adequado.

Antes de commit relevante:

```bash
git diff --check
```

Corrija problemas como trailing whitespace.

Sempre defina estratégia de teste.

Quando não houver testes automatizados suficientes, realize teste manual cobrindo:

- caminho principal;
- edição;
- validações;
- duplicidades;
- permissões;
- ativação/inativação quando aplicável;
- persistência dos dados;
- casos de erro;
- regressões relacionadas.

## 19. Regressão

Ao modificar funcionalidade existente, verifique impactos possíveis em:

- listagens;
- filtros;
- formulários;
- permissões;
- banco;
- mensagens;
- relacionamentos;
- rotas;
- telas e funcionalidades dependentes.

## 20. Logs e mensagens

Mensagens de interface devem ser claras, objetivas e consistentes.

Não exponha ao usuário final:

- stack trace;
- SQL;
- caminhos internos;
- credenciais;
- detalhes desnecessários de infraestrutura.

Erros técnicos devem ir para mecanismo de log apropriado.

Não deixe `echo`, `var_dump()` ou `print_r()` de debug permanente em produção.

## 21. Autenticação, senhas e sessões

Nunca armazene senha em texto puro.

Quando compatível com a arquitetura existente, utilize mecanismos seguros do PHP como `password_hash()` e `password_verify()`.

Para sessões, considere:

- regeneração de ID após login;
- logout adequado;
- cookies seguros sob HTTPS;
- HttpOnly;
- SameSite;
- timeout quando necessário.

Não armazene dados sensíveis desnecessários na sessão.

## 22. Performance

Não faça otimização prematura.

Antes de otimizar:

1. identifique o gargalo;
2. meça;
3. analise consultas;
4. avalie índices e volume;
5. só então altere.

Utilize `EXPLAIN` para SQL quando apropriado.

Não crie índices indiscriminadamente. Considere cardinalidade, `WHERE`, `JOIN`, `ORDER BY`, frequência das consultas, custo de escrita e índices já existentes.

## 23. Documentação

Atualize documentação quando a mudança alterar requisitos, arquitetura, migrations, módulos, comportamento ou implantação.

Locais históricos incluem:

```text
docs/
README.md
database/migrations/
```

Confirme a estrutura atual antes de criar novos diretórios.

O README deve refletir o projeto real e distinguir claramente tecnologias:

- em uso;
- planejadas;
- experimentais;
- legadas.

Decisões arquiteturais relevantes devem ser registradas em documentação apropriada já existente ou em estrutura equivalente.

Não deixe decisões importantes exclusivamente em conversas.

## 24. Módulo Localidades — contexto histórico

Há histórico de entidades:

- estados;
- cidades;
- bairros.

Também há histórico de arquivos como:

```text
backend/localidades/index.php
backend/localidades/salvar-estado.php
backend/localidades/salvar-cidade.php
backend/localidades/salvar-bairro.php
backend/localidades/bairro-form.php
backend/localidades/alterar-status-estado.php
backend/localidades/alterar-status-cidade.php
backend/localidades/alterar-status-bairro.php
database/migrations/004-localidades-editaveis.sql
```

E permissões historicamente utilizadas:

```text
LOCALIDADES_VISUALIZAR
LOCALIDADES_GERENCIAR
```

Essas informações são **contexto histórico, não garantia do estado atual**.

Confirme arquivos, schema, permissões e branch no repositório antes de utilizá-los.

## 25. Novas decisões arquiteturais

Não introduza automaticamente:

- MVC;
- API REST;
- Java/Spring Boot;
- frontend separado;
- autenticação por token;
- microsserviços;
- PostgreSQL;
- Redis;
- filas;
- Kubernetes;
- CQRS;
- arquitetura orientada a eventos;
- cloud;
- CI/CD complexo.

Antes de mudanças arquiteturais, avalie:

- problema atual;
- benefício;
- complexidade;
- custo de migração;
- compatibilidade;
- risco;
- necessidade real.

Construa para requisitos conhecidos mantendo caminhos razoáveis de evolução.

## 26. Checklist antes de considerar uma alteração concluída

Quando aplicável, confirme:

- [ ] O estado atual do projeto foi inspecionado.
- [ ] A alteração ficou dentro do escopo solicitado.
- [ ] A menor mudança correta foi utilizada.
- [ ] O código funciona no cenário principal.
- [ ] Arquivos PHP alterados passaram no lint.
- [ ] `git diff --check` está limpo.
- [ ] Entradas são validadas no servidor.
- [ ] Autorização foi preservada/verificada.
- [ ] CSRF foi preservado/verificado.
- [ ] SQL utiliza abordagem segura.
- [ ] IDs e relacionamentos são validados.
- [ ] Integridade do banco foi considerada.
- [ ] Duplicidades foram tratadas quando necessário.
- [ ] Possíveis regressões foram verificadas.
- [ ] Testes automatizados ou manuais relevantes foram executados.
- [ ] Não existem credenciais ou segredos no diff.
- [ ] Não restaram ferramentas temporárias de debug.
- [ ] Migrations foram revisadas quando aplicável.
- [ ] Documentação foi atualizada quando necessária.
- [ ] `git status` foi revisado.
- [ ] O diff final foi revisado.
- [ ] O commit representa uma unidade lógica.
- [ ] O Pull Request está preparado quando aplicável.

## 27. Regra final para agentes

Antes de qualquer alteração significativa, responda internamente a estas perguntas:

```text
O que existe hoje?
↓
Qual é exatamente o problema?
↓
Qual é a mudança mínima correta?
↓
Há impacto no banco?
↓
Há impacto de segurança?
↓
Há impacto em funcionalidades existentes?
↓
Como testar?
↓
Como versionar?
↓
Como documentar?
```

O objetivo não é produzir código rapidamente.

O objetivo é fazer o SGV9 evoluir de forma **controlada, segura, sustentável, rastreável e coerente com o projeto existente**.

