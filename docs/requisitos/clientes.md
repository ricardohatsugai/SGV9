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

Endereços e múltiplos contatos estão disponíveis na segunda entrega descrita abaixo.
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
  as relações de outros cadastros com `bairros.id`). As FKs de endereços de Clientes
  foram corrigidas na segunda entrega. A inicialização completa dos demais módulos
  merece uma revisão própria; os testes usam as tabelas envolvidas em Clientes.

## Segunda entrega: endereços e múltiplos contatos

Na listagem de Clientes, **Endereços e contatos** abre `/clientes/detalhes.php?id=...`.
A tela apresenta duas abas, filtros de ativos/inativos/todos e ações conforme as
permissões existentes de Clientes. Também há acesso pelo formulário de edição.
O cliente precisa ser salvo antes de receber endereços ou contatos.

Endereço contém tipo (faturamento, entrega ou cobrança), logradouro, número,
complemento, bairro/cidade/UF, CEP, pessoa de contato, telefone, celular, e-mail,
observação, principal e status. Tipo, logradouro, bairro e CEP são obrigatórios.
Contato contém departamento, nome, telefone, celular, e-mail, observação e status;
departamento e nome são obrigatórios. Os tipos/departamentos vêm dos dados de
referência existentes; a migration inclui os valores definidos no seed sem duplicá-los.
Esta entrega não adiciona a administração desses dados de referência.

### Regras

- Cadastro, edição, inativação e reativação; nenhuma exclusão física na interface.
- Todo filho pertence a um cliente; o cliente deve pertencer à empresa da sessão.
  IDs de filhos são conferidos junto ao cliente, inclusive nas alterações de status.
- Novos endereços e mudanças de bairro aceitam somente bairro, cidade e estado ativos.
  A edição pode manter a localidade já vinculada mesmo inativa, para preservar histórico.
  Reativação exige localidade ativa, inclusive quando realizada pelo formulário.
- Localidades bloqueia a inativação de bairros com endereços ativos de clientes ativos.
- CEP recebe validação de oito dígitos e máscara persistida; e-mail e comprimentos
  são validados no servidor. Arrays, IDs malformados e classificações inexistentes são recusados.
- Contato é único por cliente, departamento e nome, conforme a constraint existente,
  inclusive quando inativo. A mensagem orienta editar ou reativar o contato existente.
- Pode haver zero ou um endereço principal ativo. Ao salvar um endereço ativo marcado
  como principal, o anterior perde a marcação, sem ser apagado ou inativado.
  A reativação rápida de um antigo principal é recusada se houver outro principal ativo;
  a edição permite escolher explicitamente o principal ou desmarcar a opção.
- A transação bloqueia o cliente antes dos filhos, serializando alterações concorrentes.
  Uma coluna gerada e UNIQUE `(cliente_id, principal_ativo)` protegem a regra no banco.
- Auditoria usa o ID do cliente e inclui o ID do filho e os dados anteriores/novos.
  Troca do principal registra o endereço substituído. Falha no log desfaz a operação
  inteira, incluindo a troca do principal. A inativação do cliente preserva seus filhos.
- Após validação inválida, os campos permanecem disponíveis para correção.
  Observações longas aparecem resumidas na listagem; a edição preserva o texto integral.

### Migration 007 e instalações novas

`database/migrations/007-clientes-enderecos-contatos.sql` cria as quatro tabelas
ausentes no banco local inspecionado: `tipos_endereco`, `enderecos_clientes`,
`departamentos_clientes` e `contatos_clientes`. Os tipos de IDs das FKs são obtidos
dos pais, pois o banco legado usa IDs assinados e o DDL atual usa unsigned em Clientes.
Para tabelas já existentes, adiciona `ativo`, `criado_em`, `atualizado_em` e a proteção
de principal quando ausentes. Registros existentes começam ativos; timestamps novos
registram a aplicação da migration, sem reconstruir datas históricas.

Se encontrar vários principais ativos, interrompe sem apagar ou escolher endereços.
O responsável deve reconciliar as marcações explicitamente e reaplicar. DDL faz commit
implícito, portanto campos adicionados podem permanecer após uma falha parcial.
A migration é reaplicável e preserva IDs, dados e FKs. Antes de aplicar em outro
ambiente, revisar o schema e fazer backup; schemas inesperados exigem investigação.
As novas FKs dos filhos com Clientes usam DELETE RESTRICT para preservar histórico;
FKs já existentes não são reescritas. Não há rota de exclusão física de clientes.

No Docker, após a migration 006, com aplicação em manutenção:

```bash
docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysql -u root "$MYSQL_DATABASE"' < database/migrations/007-clientes-enderecos-contatos.sql
```

Reversão de estrutura exige código anterior e backup em manutenção, reconciliando
dados inseridos após a migration. Não remover tabelas como forma de rollback.
O DDL inicial já inclui os novos controles e corrige os tipos de
`tipo_endereco_id` e `bairro_id` em `enderecos_clientes` para coincidir com seus pais.

### Validação desta entrega

`bash tests/clientes.sh` verifica as migrations 006 e 007 nos modelos legado e atual,
incluindo tabelas relacionadas já existentes com dados artificiais e sem os controles
novos. Testa interrupção por principais conflitantes, preservação de dados, FKs,
unicidade e reaplicação sem duplicar tipos/departamentos.

Os testes HTTP cobrem múltiplos registros, edição, filtros, localidade histórica,
rejeição de localidades inativas em novos vínculos/reativação, contato duplicado,
validações, CSRF, permissões, IDs de outro cliente/empresa, auditoria e rollback da
troca do principal. Também verificam a dependência no módulo Localidades no banco local.
Para inspeção visual com dados fictícios, `SGV9_CAPTURE_UI=1 bash tests/clientes.sh`
salva HTML de listagens e formulário em `/tmp/sgv9-qa-clientes-*.html` no container PHP.
Esses arquivos não pertencem à aplicação nem ao Git.

Verificação manual: cadastrar dois endereços, trocar o principal, inativar/reativar,
editar endereço histórico e cadastrar dois contatos de departamentos diferentes;
conferir mensagens de duplicidade e o acesso de um perfil de leitura. As telas foram
inspecionadas no Edge com HTML capturado do servidor de testes e dados fictícios.
