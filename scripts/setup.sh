#!/usr/bin/env bash
#
# scripts/setup.sh — Configuração do ambiente de DESENVOLVIMENTO local.
#
# O que faz (pode ser executado mais de uma vez com segurança):
#   1. Verifica as ferramentas necessárias (php, composer, npm, docker)
#   2. Cria o jandiraSite/.env a partir do .env.example (se não existir)
#   3. Instala dependências PHP (composer) e Node (npm ci) e compila o frontend
#   4. Gera o APP_KEY somente se ainda estiver vazio
#   5. Garante o container PostgreSQL de desenvolvimento e espera ele ficar pronto
#   6. Executa migrations + seeders e cria o link public/storage
#
# Uso:
#   scripts/setup.sh
#
# Variáveis opcionais:
#   DEV_DB_CONTAINER  Nome do container do Postgres (padrão: postgres)
#   SKIP_DB=1         Não mexe no Docker (use se o banco for externo)
#
# Para um banco TEMPORÁRIO de testes, use scripts/test-env.sh.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "$SCRIPT_DIR/../jandiraSite" && pwd)"
ENV_FILE="$APP_DIR/.env"

DEV_DB_CONTAINER="${DEV_DB_CONTAINER:-postgres}"
DEV_DB_IMAGE="postgres:15.4-alpine"

# shellcheck source=scripts/lib/common.sh
source "$SCRIPT_DIR/lib/common.sh"

cd "$APP_DIR"

step "[1/6] Verificando pré-requisitos..."
require_commands php composer npm
if [[ "${SKIP_DB:-0}" != "1" ]]; then
    require_commands docker
fi

step "[2/6] Preparando arquivo .env..."
if [[ ! -f "$ENV_FILE" ]]; then
    cp .env.example "$ENV_FILE"
    info "Criado .env a partir do .env.example"
else
    info ".env já existe — mantido sem alterações"
fi

step "[3/6] Instalando dependências e compilando o frontend..."
composer install --no-interaction
npm ci
npm run build

step "[4/6] Verificando APP_KEY..."
if [[ -z "$(env_get APP_KEY "$ENV_FILE")" ]]; then
    php artisan key:generate --ansi
else
    info "APP_KEY já definido — mantido (gerar de novo invalidaria sessões)"
fi

step "[5/6] Preparando PostgreSQL de desenvolvimento..."
if [[ "${SKIP_DB:-0}" == "1" ]]; then
    info "SKIP_DB=1 — etapa ignorada"
else
    DB_PORT="$(env_get DB_PORT "$ENV_FILE")"
    DB_DATABASE="$(env_get DB_DATABASE "$ENV_FILE")"
    DB_USERNAME="$(env_get DB_USERNAME "$ENV_FILE")"
    DB_PASSWORD="$(env_get DB_PASSWORD "$ENV_FILE")"

    : "${DB_PORT:=5432}" "${DB_DATABASE:=jandiraDB}" "${DB_USERNAME:=postgres}"

    if container_running "$DEV_DB_CONTAINER"; then
        info "Container '$DEV_DB_CONTAINER' já está em execução"
    elif container_exists "$DEV_DB_CONTAINER"; then
        info "Iniciando container existente '$DEV_DB_CONTAINER'..."
        docker start "$DEV_DB_CONTAINER" >/dev/null
    else
        if [[ -z "$DB_PASSWORD" ]]; then
            DB_PASSWORD="$(random_password)"
            env_set DB_PASSWORD "$DB_PASSWORD" "$ENV_FILE"
            info "DB_PASSWORD vazio — senha aleatória gerada e salva no .env"
        fi

        mkdir -p "$APP_DIR/docker/postgresql/data"

        info "Criando container '$DEV_DB_CONTAINER' na porta $DB_PORT..."
        docker run -d \
            --name "$DEV_DB_CONTAINER" \
            --restart unless-stopped \
            -p "127.0.0.1:${DB_PORT}:5432" \
            -v "$APP_DIR/docker/postgresql/data:/var/lib/postgresql/data" \
            -e POSTGRES_USER="$DB_USERNAME" \
            -e POSTGRES_PASSWORD="$DB_PASSWORD" \
            -e POSTGRES_DB="$DB_DATABASE" \
            "$DEV_DB_IMAGE" >/dev/null
    fi

    wait_for_postgres "$DEV_DB_CONTAINER" "$DB_USERNAME" "$DB_DATABASE"
fi

step "[6/6] Executando migrations, seeders e storage:link..."
php artisan migrate --seed --ansi
php artisan storage:link --ansi || true

success "Configuração concluída! Rode: cd jandiraSite && composer dev"
