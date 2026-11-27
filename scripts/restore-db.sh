#!/usr/bin/env bash
#
# scripts/restore-db.sh — Restaura um backup .sql.gz no PostgreSQL do stack Docker.
#
# Uso:
#   scripts/restore-db.sh [--dev] [--yes] <arquivo.sql.gz>
#
# Opções:
#   --dev       Restaura no banco do docker-compose.dev.yml (padrão: produção)
#   --yes       Pula a confirmação interativa (cuidado em produção!)
#   -h, --help  Exibe esta ajuda

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
APP_DIR="$ROOT_DIR/jandiraSite"
ENV_FILE="$APP_DIR/.env"

# shellcheck source=scripts/lib/common.sh
source "$SCRIPT_DIR/lib/common.sh"

MODE="prod"
AUTO_YES=0
MAINTENANCE_ENABLED=0

usage() {
    sed -n '/^# Uso:/,/^set -euo/p' "${BASH_SOURCE[0]}" | sed 's/^set -euo.*//; s/^# \{0,1\}//'
}

run_compose() {
    if [[ "$MODE" == "dev" ]]; then
        compose_dev "$@"
    else
        compose_prod "$@"
    fi
}

cleanup_maintenance() {
    if [[ "$MAINTENANCE_ENABLED" -eq 1 ]]; then
        info "Desativando modo manutenção..."
        run_compose exec -T php php artisan up >/dev/null 2>&1 || true
    fi
}

main() {
    local backup_file=""

    while [[ $# -gt 0 ]]; do
        case "$1" in
            --dev)
                MODE="dev"
                shift
                ;;
            --yes)
                AUTO_YES=1
                shift
                ;;
            -h|--help)
                usage
                exit 0
                ;;
            -*)
                usage
                fail "Opção desconhecida: $1"
                ;;
            *)
                backup_file="$1"
                shift
                ;;
        esac
    done

    [[ -n "$backup_file" ]] || { usage; fail "Informe o caminho do arquivo .sql.gz."; }
    [[ -f "$backup_file" ]] || fail "Arquivo de backup não encontrado: $backup_file"

    require_docker_compose
    [[ -f "$ENV_FILE" ]] || fail "Arquivo jandiraSite/.env não encontrado."

    step "Validando integridade do arquivo compactado..."
    gzip -t "$backup_file" || fail "Arquivo corrompido ou formato gzip inválido: $backup_file"

    compose_service_running "$MODE" "postgres" \
        || fail "Serviço 'postgres' ($MODE) não está em execução."

    local db_name db_user confirm
    db_name="$(env_get DB_DATABASE "$ENV_FILE")"
    db_user="$(env_get DB_USERNAME "$ENV_FILE")"
    : "${db_name:=jandiraDB}" "${db_user:=postgres}"

    if [[ "$AUTO_YES" -ne 1 ]]; then
        warn "Esta operação substituirá os dados de '$db_name' ($MODE) usando '$backup_file'."
        printf 'Digite RESTAURAR para confirmar: '
        read -r confirm
        [[ "$confirm" == "RESTAURAR" ]] || fail "Restauração cancelada pelo usuário."
    fi

    trap cleanup_maintenance EXIT
    if compose_service_running "$MODE" "php"; then
        step "Ativando modo manutenção..."
        run_compose exec -T php php artisan down --retry=30 >/dev/null 2>&1 || true
        MAINTENANCE_ENABLED=1
    fi

    step "Restaurando backup em '$db_name' ($MODE)..."
    gunzip -c "$backup_file" | run_compose exec -T postgres \
        psql -v ON_ERROR_STOP=1 -U "$db_user" -d "$db_name" >/dev/null

    if compose_service_running "$MODE" "php"; then
        step "Limpando cache e reiniciando workers..."
        run_compose exec -T php php artisan optimize:clear --ansi
        run_compose exec -T php php artisan queue:restart --ansi >/dev/null 2>&1 || true
    fi

    cleanup_maintenance
    MAINTENANCE_ENABLED=0
    trap - EXIT

    success "Banco '$db_name' restaurado com sucesso!"
}

main "$@"
