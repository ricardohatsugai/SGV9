# Cadastro de Clientes

## Escopo da primeira entrega

Listagem com busca por nome fantasia, razão social, CNPJ (com ou sem máscara) e e-mail;
filtros de ativos, inativos e todos; cadastro, edição, inativação e reativação.
O menu Clientes é liberado conforme a permissão de visualização.

O cadastro principal contém subcategoria, data de cadastro, nome fantasia, razão
social, CNPJ, inscrições estadual e municipal, telefone, e-mail, limite de crédito,
situação cadastral, observação e status. Nome fantasia, razão social, CNPJ, data,
subcategoria e limite de crédito são obrigatórios. As categorias e subcategorias
vêm dos cadastros existentes; esta entrega não inclui sua administração.

Endereços e múltiplos contatos, embora previstos no DDL, serão entregas posteriores.
Nenhum registro de cliente é excluído fisicamente por este módulo.

## Regras e segurança

- Todas as consultas e operações sobre clientes usam a empresa da sessão.
- `CLIENTES_VISUALIZAR`: listagem; `CLIENTES_GERENCIAR`: formulário e escrita.
  Essas permissões já constam no seed e no banco de desenvolvimento inspecionado.
- Escrita exige POST e o token CSRF existente; GET não altera estado.
- IDs exigem inteiros positivos válidos; edição/status verificam existência e empresa.
- CNPJ numérico é validado pelo algoritmo existente e salvo com máscara. A unicidade
  é por empresa, validada na aplicação e protegida por `uq_clientes_empresa_cnpj`.
- Subcategoria deve existir. Campos têm limites validados no servidor; e-mail é opcional
  e validado quando preenchido. Datas inválidas e campos enviados como arrays são recusados.
- Crédito usa texto decimal, sem conversão para float: aceita ponto ou vírgula,
  até 15 dígitos inteiros e quatro casas decimais, sem milhares e sem valores negativos.
- Saídas HTML recebem escaping. Mensagens não expõem SQL ou detalhes de infraestrutura.
- Cadastro, edição e status gravam auditoria em `logs_usuarios` dentro da mesma
  transação. Falha na auditoria desfaz a alteração. Status já aplicado não duplica logs.
- Após erro de gravação, os dados do formulário são preservados na sessão para correção.

## Migration 006

O banco local inspecionado em 12/10/2026 tinha o modelo legado de Clientes,
diferente de `init-scripts/01-ddl.sql`. A migration
`database/migrations/006-clientes-cadastro.sql` adapta esse modelo sem recriar tabelas:

| Legado | Atual |
|---|---|
| categorias | categorias_clientes |
| subcategorias | subcategorias_clientes |
| datacad | data_cadastro |
| nomefantasia | nome_fantasia |
| razaosocial | razao_social |
| inscr_est / inscr_mun | inscricao_estadual / inscricao_municipal |
| subcategorias_id | subcategoria_id |
| credito | limite_credito |
| obs | observacao |

Também permite inscrição estadual/e-mail nulos, amplia e-mail para 150 caracteres,
adiciona situação cadastral e timestamps quando ausentes, e normaliza CNPJs de 14
dígitos. Os IDs, valores decimais e FKs existentes permanecem preservados. Timestamps
adicionados ao legado começam no momento da migration; não recuperam datas históricas.

A migration pode ser reaplicada e também funciona no schema atual. Ela recusa colunas
ambíguas/ausentes, tabelas legadas e atuais coexistentes, outras dependências das
categorias legadas, CNPJs fora do formato e CNPJs equivalentes duplicados.
Não resolve conflitos apagando registros. Dependências externas ao banco devem ser
revisadas antes de renomear tabelas em outro ambiente.

Execute com aplicação em manutenção, backup verificado e banco explicitamente
selecionado. DDL no MySQL faz commit implícito: não há rollback automático da migration.
Se ocorrer uma falha parcial, corrija a causa e reaplique; a procedure auxiliar é
recriada a cada execução. Para reverter a estrutura completa, use o backup anterior
com o código anterior em manutenção; não restaure sobre dados novos sem reconciliá-los.
O usuário executor precisa de permissão para criar/executar/remover a procedure e alterar tabelas.

No ambiente Docker, a partir da raiz do projeto e com o serviço `db` ativo:

```bash
docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysql -u root "$MYSQL_DATABASE"' < database/migrations/006-clientes-cadastro.sql
```

Instalações novas já têm os campos no DDL. A migration garante a padronização de
CNPJs em bancos existentes; ela não representa validação da inicialização integral
de todos os módulos.

## Validação automatizada

```bash
bash tests/clientes.sh
```

Requer Docker Compose ativo e Python 3 no WSL. O script cria somente bancos
descartáveis `sgv9_clientes_test_<numero>` no MySQL local e os remove ao terminar.
Usa uma cópia sem dados do schema local, reconstruindo nela os nomes/campos legados
observados, e, separadamente, as tabelas de Clientes extraídas do DDL atual.
Não copia registros, senhas nem sessões reais.
As fixtures são artificiais; o servidor HTTP de testes é interno ao container PHP.

Cobre preservação de dados/FKs/precisão na migration, conflito de CNPJ, reaplicação,
unicidade no banco, cadastro, edição, filtros, XSS, validações, CSRF, permissões,
isolamento por empresa, status, timestamps, auditoria e rollback. No schema local
completo, também verifica dashboard e listagens de Localidades e Representadas.

## Verificação manual

1. Entrar com usuário autorizado e acessar Clientes no menu.
2. Cadastrar cliente, buscar por nome e CNPJ, editar e confirmar persistência.
3. Tentar CNPJ duplicado/inválido, e-mail/data/crédito inválidos e subcategoria inexistente.
4. Inativar, conferir filtros e reativar.
5. Conferir usuário de leitura e acesso a IDs de outra empresa.
6. Conferir dashboard, Localidades e Representadas, incluindo seletores de localidades.

## Pendências fora do escopo

- A busca de Representadas reutiliza o mesmo placeholder nomeado, apesar de o PDO
  usar prepared statements nativos. A regressão aqui cobre a listagem sem busca;
  a busca desse módulo deve ser corrigida separadamente.
- O DDL inicial contém tipos divergentes em FKs de endereços (por exemplo,
  `tipos_endereco.id` e `enderecos_clientes.tipo_endereco_id`). A inicialização completa
  merece uma revisão própria; os testes desta entrega usam as tabelas envolvidas em Clientes.
