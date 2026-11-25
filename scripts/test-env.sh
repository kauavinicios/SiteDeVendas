#!/usr/bin/env bash
#
# scripts/test-env.sh — Ambiente de TESTE com banco PostgreSQL temporário.
#
# O container é criado com --rm e os dados ficam em tmpfs (memória):
# ao parar o container, TUDO é descartado. Não interfere no banco de
# desenvolvimento (container/porta diferentes).
#
#   Container : jandira-db-test  (porta 127.0.0.1:5433)
#   Bancos    : jandira_homolog  -> usado pelo "php artisan serve" (APP_ENV=homolog)
#               jandira_phpunit  -> usado pela suíte de testes (phpunit.xml)
#   Env file  : jandiraSite/.env.homolog (gerado automaticamente, ignorado pelo Git)
#
# Uso:
#   scripts/test-env.sh up       Sobe o banco, gera .env.homolog e roda migrate:fresh --seed
#   scripts/test-env.sh serve    Inicia o servidor em http://127.0.0.1:8000 (APP_ENV=homolog)
#   scripts/test-env.sh start    up + serve; ao sair (Ctrl+C) o container é removido
#   scripts/test-env.sh reset    Recria o banco de homologação (migrate:fresh --seed)
#   scripts/test-env.sh test     Roda "php artisan test" (aceita argumentos extras)
#   scripts/test-env.sh down     Para e remove o container (dados descartados)
#   scripts/test-env.sh status   Mostra o estado do container
#
# Variáveis opcionais: TEST_DB_PORT (5433), SERVE_HOST (127.0.0.1), SERVE_PORT (8000)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "$SCRIPT_DIR/../jandiraSite" && pwd)"

# shellcheck source=scripts/lib/common.sh
source "$SCRIPT_DIR/lib/common.sh"

# Devem bater com o <php><env> do jandiraSite/phpunit.xml e com o tests.yml
CONTAINER="jandira-db-test"
IMAGE="postgres:18-alpine"
DB_PORT="${TEST_DB_PORT:-5433}"
DB_USER="jandira"
DB_PASS="jandira_test"
DB_HOMOLOG="jandira_homolog"
DB_PHPUNIT="jandira_phpunit"

APP_ENV_NAME="homolog"
ENV_FILE="$APP_DIR/.env.$APP_ENV_NAME"
SERVE_HOST="${SERVE_HOST:-127.0.0.1}"
SERVE_PORT="${SERVE_PORT:-8000}"

# Executa artisan no ambiente de homologação (carrega o .env.homolog).
# O APP_ENV vai só para este comando: não "vaza" para o "test".
artisan_homolog() {
    (cd "$APP_DIR" && APP_ENV="$APP_ENV_NAME" php artisan "$@")
}

check_app_ready() {
    [[ -f "$APP_DIR/vendor/autoload.php" ]] \
        || fail "Dependências PHP ausentes. Rode primeiro: scripts/setup.sh (ou composer install)."

    if [[ ! -f "$APP_DIR/public/build/manifest.json" ]]; then
        step "Frontend não compilado — executando npm run build..."
        (cd "$APP_DIR" && { [[ -d node_modules ]] || npm ci; } && npm run build)
    fi
}

db_up() {
    require_commands docker php

    if container_running "$CONTAINER"; then
        info "Container '$CONTAINER' já está em execução"
    else
        step "Subindo PostgreSQL temporário ($CONTAINER, porta $DB_PORT)..."
        docker run -d --rm \
            --name "$CONTAINER" \
            -p "127.0.0.1:${DB_PORT}:5432" \
            --tmpfs /var/lib/postgresql \
            -e POSTGRES_USER="$DB_USER" \
            -e POSTGRES_PASSWORD="$DB_PASS" \
            -e POSTGRES_DB="$DB_HOMOLOG" \
            "$IMAGE" >/dev/null
    fi

    wait_for_postgres "$CONTAINER" "$DB_USER" "$DB_HOMOLOG"

    # Banco separado para o phpunit: o RefreshDatabase dos testes não
    # apaga os dados que você está usando no navegador.
    if ! docker exec "$CONTAINER" psql -U "$DB_USER" -d "$DB_HOMOLOG" -tAc \
        "SELECT 1 FROM pg_database WHERE datname = '$DB_PHPUNIT'" | grep -q 1; then
        docker exec "$CONTAINER" psql -U "$DB_USER" -d "$DB_HOMOLOG" -qc "CREATE DATABASE $DB_PHPUNIT" >/dev/null
        info "Banco '$DB_PHPUNIT' criado"
    fi
}

prepare_env_file() {
    if [[ ! -f "$ENV_FILE" ]]; then
        cp "$APP_DIR/.env.example" "$ENV_FILE"
        info "Criado $(basename "$ENV_FILE") a partir do .env.example"
    fi

    # Sempre reaplica as configurações do banco temporário
    env_set APP_ENV "$APP_ENV_NAME" "$ENV_FILE"
    env_set APP_DEBUG true "$ENV_FILE"
    env_set APP_URL "http://${SERVE_HOST}:${SERVE_PORT}" "$ENV_FILE"
    env_set DB_CONNECTION pgsql "$ENV_FILE"
    env_set DB_HOST 127.0.0.1 "$ENV_FILE"
    env_set DB_PORT "$DB_PORT" "$ENV_FILE"
    env_set DB_DATABASE "$DB_HOMOLOG" "$ENV_FILE"
    env_set DB_USERNAME "$DB_USER" "$ENV_FILE"
    env_set DB_PASSWORD "$DB_PASS" "$ENV_FILE"

    if [[ -z "$(env_get APP_KEY "$ENV_FILE")" ]]; then
        artisan_homolog key:generate --ansi
    fi
}

cmd_up() {
    check_app_ready
    db_up
    step "Preparando $(basename "$ENV_FILE")..."
    prepare_env_file
    cmd_reset
    artisan_homolog storage:link --ansi >/dev/null 2>&1 || true
    success "Ambiente de teste pronto. Login: teste@gmail.com / 12345678"
    info "Inicie o servidor com: scripts/test-env.sh serve"
}

cmd_reset() {
    step "Recriando banco de homologação (migrate:fresh --seed)..."
    artisan_homolog migrate:fresh --seed --force --ansi
}

cmd_serve() {
    container_running "$CONTAINER" || fail "Banco temporário não está rodando. Rode: scripts/test-env.sh up"
    [[ -f "$ENV_FILE" ]] || fail "$(basename "$ENV_FILE") não encontrado. Rode: scripts/test-env.sh up"
    step "Servidor em http://${SERVE_HOST}:${SERVE_PORT} (Ctrl+C para parar)"
    artisan_homolog serve --host="$SERVE_HOST" --port="$SERVE_PORT"
}

cmd_start() {
    cmd_up
    trap 'cmd_down' EXIT
    cmd_serve
}

cmd_test() {
    container_running "$CONTAINER" || fail "Banco temporário não está rodando. Rode: scripts/test-env.sh up"
    (cd "$APP_DIR" && php artisan test "$@")
}

cmd_down() {
    if container_exists "$CONTAINER"; then
        step "Removendo container '$CONTAINER' (dados descartados)..."
        docker stop "$CONTAINER" >/dev/null
        success "Ambiente de teste encerrado"
    else
        info "Container '$CONTAINER' não está em execução"
    fi
}

cmd_status() {
    if container_running "$CONTAINER"; then
        docker ps -f "name=^${CONTAINER}$" --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'
    else
        info "Container '$CONTAINER' parado/inexistente"
    fi
}

usage() {
    sed -n '/^# Uso:/,/^# Variáveis/p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//'
}

main() {
    local cmd="${1:-}"
    shift || true
    case "$cmd" in
        up)     cmd_up ;;
        serve)  cmd_serve ;;
        start)  cmd_start ;;
        reset)  cmd_reset ;;
        test)   cmd_test "$@" ;;
        down)   cmd_down ;;
        status) cmd_status ;;
        *)      usage; [[ -z "$cmd" ]] || exit 1 ;;
    esac
}

main "$@"
