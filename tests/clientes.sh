#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

# Somente bancos descartáveis com este prefixo são criados/removidos.
test_db="sgv9_clientes_test_$(date +%s)${RANDOM}"
[[ "$test_db" =~ ^sgv9_clientes_test_[0-9]+$ ]] || exit 1
php_container=$(docker compose ps -q php)
[[ -n "$php_container" ]] || { echo 'Serviço PHP não está ativo.'; exit 1; }
mysql_test() {
    docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysql -u root "$1"' sh "$test_db"
}
cleanup() {
    echo "Removendo somente o banco descartável $test_db."
    printf 'DROP DATABASE `%s`;\n' "$test_db" | docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysql -u root'
}
trap cleanup EXIT
docker cp tests/clientes.php "$php_container":/tmp/sgv9-clientes-test.php
docker cp tests/migration-clientes.php "$php_container":/tmp/sgv9-migration-clientes-test.php
for schema in legado novo; do
docker compose exec -T db sh -c '
    MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -u root -e "CREATE DATABASE \`$1\` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci; GRANT ALL ON \`$1\`.* TO '\''$MYSQL_USER'\''@'\''%'\'';"
' sh "$test_db"

echo "Testando schema $schema em banco isolado."
if [[ "$schema" == legado ]]; then
    docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysqldump -u root --no-data --no-tablespaces --set-gtid-purged=OFF "$MYSQL_DATABASE"' | mysql_test
    docker compose exec -T -e DB_DATABASE="$test_db" php php /tmp/sgv9-migration-clientes-test.php preparar-legado
else
    python3 tests/schema-clientes.py | mysql_test
fi
docker compose exec -T -e DB_DATABASE="$test_db" php php /tmp/sgv9-migration-clientes-test.php seed
docker compose exec -T -e DB_DATABASE="$test_db" php php /tmp/sgv9-migration-clientes-test.php duplicate
if mysql_test < database/migrations/006-clientes-cadastro.sql; then
    echo 'ERRO: migration aceitou CNPJs equivalentes duplicados.'
    exit 1
fi
docker compose exec -T -e DB_DATABASE="$test_db" php php /tmp/sgv9-migration-clientes-test.php remove-duplicate
mysql_test < database/migrations/006-clientes-cadastro.sql
mysql_test < database/migrations/006-clientes-cadastro.sql
docker compose exec -T -e DB_DATABASE="$test_db" php php /tmp/sgv9-migration-clientes-test.php verify
docker compose exec -T -e DB_DATABASE="$test_db" -e SGV9_TEST_SCHEMA="$schema" php php /tmp/sgv9-clientes-test.php
cleanup
done
trap - EXIT
