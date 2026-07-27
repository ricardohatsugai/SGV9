# SGV9 Admin V2

Inclui validação matemática de CNPJ no navegador e no servidor, cadastro de estados, cidades e bairros, menu Localidades e atalho no formulário de representadas.

## Atualização do banco

Execute uma vez o arquivo:

`database/03-localidades-permissoes.sql`

Exemplo:

```bash
docker compose exec -T db mysql -u root -p"$MYSQL_ROOT_PASSWORD" sgv9 < backend/database/03-localidades-permissoes.sql
```

Depois reconstrua os serviços PHP e web e faça logout/login para recarregar as permissões.
