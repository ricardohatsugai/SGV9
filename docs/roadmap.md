# Roadmap do SGV9

O SGV9 evolui como aplicação PHP/MySQL independente. A referência funcional é
registrada em [requisitos/paridade-sgv10.md](requisitos/paridade-sgv10.md), com
distinção entre implementações e recursos apenas planejados no SGV10.

## Base existente

Login/sessão, permissões por perfil, dashboard de contagens, usuários,
representadas e localidades. Conferir o código e o schema em cada entrega.

## Ordem das entregas

1. **Clientes:** cadastro básico entregue; completar endereços, contatos, documentos
   e vínculos com vendedores. Harmonizar o schema de forma versionada por módulo.
2. **Cadastros comerciais:** vendedores, condições de pagamento e complementos
   de representadas, preservando localidades e histórico.
3. **Produtos e preços:** catálogo, classificação, unidades, embalagem, dados fiscais,
   tabelas de preços e políticas comerciais previstas no SGV9.
4. **Pedidos e propostas:** itens, descontos sucessivos, valores históricos,
   acompanhamento, conversão vinculada, operações em lote e impressão/PDF.
5. **Atendimento dos pedidos:** notas parciais, estornos, cancelamento/reversão de
   saldo, entregas/cobranças e consulta de pendências.
6. **Comissões:** negociação por cliente/documento/item, títulos, liberação por
   faturamento/quitação e relatórios. Não confundir liberação com pagamento.
7. **Gestão comercial:** painel de prioridades, dashboard mensal, metas, clientes
   sem compras, ranking/ABC, ficha comercial e sugestão de propostas.
8. **Importações e demais extensões:** assistente XLSX/CSV com prévia e auditoria;
   agenda, financeiro e integrações previstos no SGV9.

Cada entrega inclui validação de segurança e relacionamentos, testes em banco
separado, revisão do diff, migrations quando necessárias e atualização documental.
Paridade completa será declarada somente após validar todas as funcionalidades
identificadas na matriz; o cadastro básico de clientes é a primeira etapa.
