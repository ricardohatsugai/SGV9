# Evolução do SGV9 com referência funcional no SGV10

## Decisão e fontes

Decisão registrada em 12/10/2026: SGV9 e SGV10 são projetos separados. O SGV10
é referência de funcionalidades e regras comerciais. As alterações desta iniciativa
são feitas no SGV9, mantendo PHP 8.x, MySQL 8.4 e a organização modular existente.
Não há compartilhamento de banco nem migração de dados do SGV10 nesta iniciativa.

O levantamento consultou os fontes e a documentação disponíveis em `C:\sgv10`.
Cada entrega deve conferir novamente o módulo correspondente, pois a referência
pode evoluir. Os nomes dos documentos abaixo são relativos à pasta `docs/` dessa
referência. Recursos apenas propostos são identificados como tal.

## Matriz funcional inicial

| Área | Referência no SGV10 | Estado no SGV9 nesta entrega |
| --- | --- | --- |
| Navegação e identidade visual | `UFrmPrincipal.pas` | Interface web existente; identidade específica a avaliar. |
| Clientes: consulta, cadastro e edição | `UFrmClientes.pas`, `UClienteServico.pas` | Cadastro básico entregue: CNPJ numérico, classificação, contato e crédito. |
| Clientes: documentos e contatos | `UClienteValidacao.pas` | CPF, CNPJ alfanumérico, validação de telefones e preservação de campos legados a implementar. |
| Clientes: endereços comercial, entrega e cobrança | `UClienteServico.pas`, `UClienteRepositorio.pas` | A implementar; tabelas de endereços ausentes no banco local inspecionado. |
| Clientes: pesquisa por cidade e vendedor | `UClienteRepositorio.pas` | A implementar após os relacionamentos necessários. |
| Estados e cidades | `CadastrosAuxiliares.md` | Localidades já existentes; revisar paridade em cada entrega. |
| Vendedores e condições de pagamento | `CadastrosAuxiliares.md` | Telas a implementar com o schema do SGV9. |
| Fábricas e representadas | `FabricasRepresentadas.md` | Módulo existente; razão social, contatos e efeitos no catálogo a revisar. |
| Produtos | `CadastroProdutos.md` | A implementar: identificação, referência por representada, unidade, embalagem, preços e dados fiscais. |
| Pedidos | `PedidosVenda.md` | A implementar: cabeçalho, itens, pesquisa, descontos, preço fixo, totais e proteção de documentos atendidos. |
| Propostas | `PropostasComerciais.md` | A implementar: cadastro próprio, edição, exclusão individual/em lote e conversão com vínculo ao pedido. |
| Acompanhamento de propostas | `AcompanhamentoPropostas.md` | A implementar: situação comercial, validade e próximo contato, separados do vínculo com pedidos. |
| Impressão/PDF | `ImpressaoPedidos.md` | A implementar: pedidos e propostas com valores históricos, logo e paginação. |
| Faturamento parcial e estorno | `FaturamentoPedidos.md` | A implementar: notas, quantidades por item, saldos e histórico de estorno. |
| Cancelamento/reversão de saldo | `CancelamentoSaldoPedidos.md` | A implementar sem reescrever quantidades comerciais originais. |
| Pendências comerciais | `PedidosPendentes.md` | A implementar: consolidação por representada/cliente e documentos a conferir. |
| Entregas e cobranças | `AcompanhamentoEntregas.md` | A implementar: previsão, contatos e histórico. |
| Prioridades do dia | `PainelComercial.md` | A implementar após propostas, pedidos e acompanhamento. |
| Dashboard de pedidos emitidos | `DashboardPedidos.md` | Dashboard existente contém somente contagens; indicadores comerciais a implementar. |
| Metas mensais | `MetasMensais.md` | A implementar por mês, vendedor e representada. |
| Curva ABC e sugestão de proposta | `SugestaoPedidoABC.md` | A implementar com histórico válido e conferência antes de gravar. |
| Análise comercial de clientes | `ClientesComercial.md` | A implementar: sem compras, ranking/ABC, ficha comercial e impressão. |
| Comissões | `ComissoesVendedores.md` | A implementar: cliente/documento/item, liberação por faturamento ou quitação, títulos e relatório. |
| Importação configurável XLSX/CSV | `ImportacaoProdutosProposta.md` | Planejada na referência; incluir no planejamento do SGV9 sem anunciar implementação existente. |

A matriz cobre as funcionalidades identificadas no levantamento inicial. Uma linha
entregue parcialmente não representa paridade completa. Agenda, tabelas de preços,
financeiro e integrações já previstos no SGV9 permanecem no planejamento próprio,
sem presumir que estejam implementados no SGV10.

## Regras comerciais a preservar nas etapas seguintes

- Um cliente e uma representada por documento de venda.
- Descontos sucessivos, até seis na referência, aplicados na inclusão do item.
  O preço líquido histórico não recebe os descontos novamente ao somar o documento.
- Precisão de quatro casas decimais e arredondamento explícito nos cálculos;
  não usar ponto flutuante binário para valores comerciais.
- Propostas ficam separadas de pedidos e não entram nas vendas realizadas.
- Conversão preserva os preços negociados, confere produtos/embalagens e registra
  o pedido, seus itens e o vínculo com a proposta na mesma transação.
- Representadas inativas bloqueiam novas vendas e continuam disponíveis no histórico
  e no acompanhamento de pedidos existentes.
- Faturamento, estorno e cancelamento/reversão mantêm eventos e motivos; não apagam
  o histórico nem sobrescrevem os valores originais dos documentos.
- Dashboard e metas usam pedidos emitidos; saldos pendentes e faturamento são bases
  distintas, com critérios documentados para cada relatório.
- Metas são únicas por mês/vendedor/representada. Vendas sem meta não fazem uma
  meta de outro par parecer atingida.
- Comissão usa a base líquida sem IPI/frete, preserva a negociação histórica e permite
  exceções por item. Comissão liberada não equivale a pagamento ao vendedor.
- Relatórios ABC excluem registros inconsistentes dos indicadores, identificam itens
  a conferir e mantêm os critérios e filtros na impressão.
- Importações terão prévia, origem por campo, correspondência por representada,
  tratamento explícito de ambiguidades e gravação atômica. Identificadores são texto.

## Divergência de banco identificada

O banco local em execução não corresponde integralmente ao `init-scripts/01-ddl.sql`.
Em Clientes foram confirmados:

| Banco local | DDL versionado |
| --- | --- |
| `datacad` | `data_cadastro` |
| `nomefantasia` / `razaosocial` | `nome_fantasia` / `razao_social` |
| `inscr_est` / `inscr_mun` | `inscricao_estadual` / `inscricao_municipal` |
| `subcategorias_id` | `subcategoria_id` |
| `credito` / `obs` | `limite_credito` / `observacao` |
| `categorias` / `subcategorias` | `categorias_clientes` / `subcategorias_clientes` |
| E-mail de até 100 caracteres, obrigatório no SQL | Até 150 caracteres, opcional no SQL |

O adaptador local do módulo suporta esses dois conjuntos conhecidos por um mapa
interno de nomes. Entrada externa não escolhe tabelas ou colunas. Campos opcionais
vazios são enviados como texto vazio, compatível com as constraints locais. Nenhuma
coluna/tabela comercial foi renomeada, removida ou criada por esta entrega.

A harmonização completa do banco será uma entrega própria, com export do schema,
backup validado, migrations e testes de todas as dependências. Scripts Firebird do
SGV10 não são aplicáveis ao MySQL. Também não se presume que executar o DDL inteiro
em um banco novo funcione: esta entrega testou somente as estruturas necessárias
ao módulo de clientes, nos dois formatos.

## Entrega 1: cadastro básico de clientes

- Listagem por empresa, busca por código/nome/razão social/CNPJ/e-mail, filtro de
  ativos/inativos/todos e paginação de 25 registros.
- Inclusão e edição de dados cadastrais, data, categoria/subcategoria existente,
  telefone, e-mail, crédito e observação.
- CNPJ numérico validado e formatado; duplicidade verificada por empresa na aplicação
  e protegida pela constraint existente `uq_clientes_empresa_cnpj` para a representação
  padronizada gravada pelo módulo. Escritores externos que gravem documentos com outra
  pontuação exigem futura normalização/integridade específica.
- Validação de IDs, classificação existente, tamanho dos campos, datas, e-mail,
  status e precisão de `DECIMAL(19,4)` no servidor.
- Ativação/inativação, preservando registros e relacionamentos.
- Permissões `CLIENTES_VISUALIZAR` e `CLIENTES_GERENCIAR`, CSRF, escaping de saída,
  prepared statements nativos e escopo de empresa obtido da sessão.
- Gravação e auditoria na mesma transação; comparação com a versão exibida para
  recusar edição/status sobre um registro alterado em outra sessão.
- Recuperação dos campos preenchidos após falha de validação.

### Habilitação

Aplicar `database/migrations/006-clientes-permissoes.sql` com a conexão administrativa
do ambiente. Ela insere permissões ausentes e associa visualização/gerenciamento a
ADMINISTRADOR, VENDEDOR e ASSISTENTE, conforme a política de `02-dml.sql`. Reexecução
mantém as associações existentes. Não acrescenta gerenciamento aos perfis de consulta.

Após a aplicação, sair e entrar novamente para recarregar as permissões da sessão.
O acesso é **Clientes** no menu lateral. Não há exclusão física na interface.

A migration 006 foi aplicada ao banco local do SGV9 em 12/10/2026, depois dos testes
isolados. A conferência manteve a contagem de clientes e habilitou gerenciamento
somente para os três perfis previstos. As alterações de código permanecem na branch
`codex/clientes-paridade-sgv10` para revisão.

### Testes

No WSL, identificar os containers com `docker compose ps` e executar:

```bash
python3 tests/executar_clientes.py --php-container <container-php> --db-container <container-mysql>
```

O runner lê somente o schema do banco local, monta uma base sem dados comerciais
para esse formato e outra para as tabelas necessárias extraídas do DDL versionado.
Ele exige MySQL com a configuração administrativa local de `MYSQL_ROOT_PASSWORD`,
cria bancos com prefixo exclusivo `sgv9_test_clientes_`, concede acesso apenas a esses
bancos e os remove ao final. Arquivos/sessões do servidor de teste ficam em uma pasta
exclusiva no `/tmp` do container PHP e também são removidos.

Os testes cobrem persistência, duplicidades, isolamento por empresa, validações,
precisão decimal, auditoria, rollback, concorrência, migration reexecutável e rotas
HTTP com autenticação, permissões, CSRF, XSS, filtros e preservação do formulário.
Não utilizam o banco ou o executável do SGV10.
