#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker não encontrado. Instale o Docker Desktop com integração WSL2."
  exit 1
fi

if [ ! -f .env ]; then
  cp .env.example .env
  echo "Arquivo .env criado. Revise as senhas antes de continuar:"
  echo "  nano .env"
  exit 0
fi

docker compose up -d --build
docker compose ps

echo
echo "SGV9 iniciado:"
echo "  Sistema:    http://localhost"
echo "  phpMyAdmin: http://localhost:8080"
echo "  Login:      admin@sgv9.com.br"
echo "  Senha:      password"
