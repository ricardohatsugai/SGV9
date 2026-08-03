# SGV9 — Implementação de edição e inativação de localidades

## Escopo implementado no banco

- Edição de nomes de estados, cidades e bairros preservando os respectivos IDs.
- Edição da sigla do estado.
- Inativação lógica por meio do campo `ativo`.
- Registro automático de `criado_em` e `atualizado_em`.
- Restrição de cidade única por nome dentro do mesmo estado.
- Manutenção da restrição de bairro único por nome dentro da mesma cidade.
- Correção da sigla da Bahia de `BH` para `BA`.
- Correção de ocorrências corrompidas de `SÃO PAULO`.

## Regras obrigatórias no backend

1. Não excluir fisicamente localidades pela interface.
2. Nas listas usadas em novos cadastros, retornar apenas registros ativos.
3. Nas telas administrativas, permitir filtrar por ativos, inativos ou todos.
4. Antes de inativar um estado, alertar quando existirem cidades ativas vinculadas.
5. Antes de inativar uma cidade, alertar quando existirem bairros ativos vinculados.
6. Não inativar automaticamente registros filhos. A decisão deve ser explícita.
7. Normalizar entrada com `trim`; recomenda-se salvar nomes e siglas em maiúsculas.
8. Converter erro de chave única em mensagem amigável.

## Rotas sugeridas

### Estados
- `GET /api/estados?status=ativos|inativos|todos`
- `POST /api/estados`
- `PUT /api/estados/{id}`
- `PATCH /api/estados/{id}/status`

Payload de edição:
```json
{
  "nome": "GOIÁS",
  "sigla": "GO"
}
```

Payload de status:
```json
{
  "ativo": false
}
```

### Cidades
- `GET /api/cidades?estado_id={id}&status=ativos|inativos|todos`
- `POST /api/cidades`
- `PUT /api/cidades/{id}`
- `PATCH /api/cidades/{id}/status`

Payload de edição:
```json
{
  "nome": "MINEIROS",
  "estado_id": 9
}
```

### Bairros
- `GET /api/bairros?cidade_id={id}&status=ativos|inativos|todos`
- `POST /api/bairros`
- `PUT /api/bairros/{id}`
- `PATCH /api/bairros/{id}/status`

Payload de edição:
```json
{
  "nome": "JARDIM AMÉRICA",
  "cidade_id": 1
}
```

## Comportamento da interface

Cada listagem deve apresentar:

- Busca por nome.
- Filtro de situação.
- Ação **Editar**.
- Ação **Inativar** ou **Reativar**.
- Identificação visual de registros inativos.
- Confirmação antes da mudança de status.

Não deve existir botão de exclusão física nessa primeira versão.

## Critérios de aceite

- Alterar o nome de uma localidade não muda seu ID.
- Os relacionamentos existentes permanecem válidos após a edição.
- Não é possível cadastrar duas cidades com o mesmo nome no mesmo estado.
- Não é possível cadastrar dois bairros com o mesmo nome na mesma cidade.
- Localidades inativas não aparecem nos seletores de novos cadastros.
- Localidades inativas continuam visíveis em registros históricos.
- É possível reativar uma localidade.
- A data `atualizado_em` muda automaticamente após uma edição ou mudança de status.

## Aplicação em banco existente

```bash
docker exec -i sgv9_mysql mysql \
  -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" \
  < 04-localidades-editaveis.sql
```

## Banco novo

Substitua os arquivos de inicialização pelos arquivos atualizados:

- `01-ddl.sql`
- `02-dml.sql`

Depois recrie o volume somente em ambiente de desenvolvimento, pois isso apaga os dados:

```bash
docker compose down -v
docker compose up -d --build
```

## Limite desta entrega

Os arquivos de backend e frontend não estavam presentes no material disponibilizado. Portanto, esta entrega implementa integralmente a camada de banco e documenta o contrato necessário para a interface e a API.


---

# Edição e Gerenciamento de Localidades

## Objetivo

Permitir o gerenciamento completo dos cadastros de estados, cidades e bairros, preservando a integridade dos dados e o histórico das informações cadastradas.

## Funcionalidades

As seguintes funcionalidades passaram a fazer parte do módulo de Localidades:

- Cadastro de Estados;
- Cadastro de Cidades;
- Cadastro de Bairros;
- Edição de Estados;
- Edição de Cidades;
- Edição de Bairros;
- Inativação de Estados;
- Inativação de Cidades;
- Inativação de Bairros;
- Reativação de Estados;
- Reativação de Cidades;
- Reativação de Bairros.

## Regras de Negócio

- Os registros não devem ser excluídos fisicamente quando houver relacionamentos com outros módulos do sistema.
- A inativação preserva todo o histórico de utilização da localidade.
- Localidades inativas não devem ser apresentadas nos cadastros de novos registros.
- Durante a edição de um cadastro já existente, a localidade atualmente vinculada continua disponível para seleção, mesmo que esteja inativa.
- O sistema impede o cadastro de cidades duplicadas dentro do mesmo estado.
- O sistema impede o cadastro de bairros duplicados dentro da mesma cidade.

## Alterações no Banco de Dados

As tabelas abaixo passaram a possuir os seguintes campos de controle:

### estados

- ativo
- criado_em
- atualizado_em

### cidades

- ativo
- criado_em
- atualizado_em

### bairros

- ativo
- criado_em
- atualizado_em

Além disso, a tabela **cidades** possui restrição de unicidade para impedir registros duplicados utilizando a combinação:

```text
estado_id + nome
```

## Migração

As alterações estruturais desta funcionalidade são aplicadas através da migration:

```text
database/migrations/004-localidades-editaveis.sql
```

Para instalações novas, essas alterações já fazem parte do arquivo:

```text
init-scripts/01-ddl.sql
```

## Impacto em Outros Módulos

O cadastro de Representadas passou a considerar apenas localidades ativas durante novos cadastros.

Na edição de um registro existente, a localidade atualmente vinculada permanece disponível para preservar a consistência dos dados históricos.

# Histórico

| Versão | Data | Alteração |
|---------|------|-----------|
| 0.1.0 | 03/08/2026 | Inclusão de edição, inativação, reativação e auditoria de localidades. |