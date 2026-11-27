#!/usr/bin/env bash
#
# scripts/backup-db.sh — Gera backup compactado do PostgreSQL com rotação automática.
#
# Uso:
#   scripts/backup-db.sh [--dev] [-h|--help]
#
# Opções:
#   --dev       Usa o banco do docker-compose.dev.yml (padrão: produção)
#   -h, --help  Exibe esta ajuda
#
# Variáveis opcionais:
#   BACKUP_DIR              Diretório de destino (padrão: ./backups)
#   BACKUP_RETENTION_COUNT  Quantidade máxima de backups mantidos (padrão: 14)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
APP_DIR="$ROOT_DIR/jandiraSite"
ENV_FILE="$APP_DIR/.env"

# shellcheck source=scripts/lib/common.sh
source "$SCRIPT_DIR/lib/common.sh"

MODE="prod"
BACKUP_DIR="${BACKUP_DIR:-$ROOT_DIR/backups}"
RETENTION="${BACKUP_RETENTION_COUNT:-14}"
TARGET_FILE=""

usage() {
    sed -n '/^# Uso:/,/^set -euo/p' "${BASH_SOURCE[0]}" | sed 's/^set -euo.*//; s/^# \{0,1\}//'
}

cleanup_partial() {
    if [[ -n "$TARGET_FILE" && -f "$TARGET_FILE" ]]; then
        rm -f "$TARGET_FILE"
    fi
}

run_compose() {
    if [[ "$MODE" == "dev" ]]; then
        compose_dev "$@"
    else
        compose_prod "$@"
    fi
}

main() {
    while [[ $# -gt 0 ]]; do
        case "$1" in
            --dev)
                MODE="dev"
                shift
                ;;
            -h|--help)
                usage
                exit 0
                ;;
            *)
                usage
                fail "Opção desconhecida: $1"
                ;;
        esac
    done

    require_docker_compose
    [[ -f "$ENV_FILE" ]] || fail "Arquivo jandiraSite/.env não encontrado."

    compose_service_running "$MODE" "postgres" \
        || fail "Serviço 'postgres' ($MODE) não está em execução."

    local db_name db_user timestamp tmp_file final_file size
    db_name="$(env_get DB_DATABASE "$ENV_FILE")"
    db_user="$(env_get DB_USERNAME "$ENV_FILE")"
    : "${db_name:=jandiraDB}" "${db_user:=postgres}"

    umask 077
    mkdir -p "$BACKUP_DIR"
    chmod 700 "$BACKUP_DIR"

    timestamp="$(date +%Y%m%d_%H%M%S)"
    final_file="$BACKUP_DIR/jandira_${db_name}_${timestamp}.sql.gz"
    tmp_file="${final_file}.tmp"
    TARGET_FILE="$tmp_file"
    trap cleanup_partial EXIT

    step "Gerando backup de '$db_name' ($MODE)..."
    run_compose exec -T postgres \
        pg_dump -U "$db_user" -d "$db_name" --no-owner --no-acl --clean --if-exists \
        | gzip -9 > "$tmp_file"

    gzip -t "$tmp_file" || fail "Falha na verificação de integridade do arquivo .sql.gz."
    mv "$tmp_file" "$final_file"
    TARGET_FILE=""
    trap - EXIT

    if [[ "$RETENTION" =~ ^[0-9]+$ ]] && (( RETENTION > 0 )); then
        mapfile -t old_backups < <(
            find "$BACKUP_DIR" -maxdepth 1 -type f -name 'jandira_*.sql.gz' -printf '%T@ %p\n' \
                | sort -rn \
                | tail -n "+$((RETENTION + 1))" \
                | cut -d ' ' -f 2-
        )
        if [[ ${#old_backups[@]} -gt 0 ]]; then
            rm -f -- "${old_backups[@]}"
            info "Rotação aplicada (${#old_backups[@]} backup(s) antigo(s) removido(s), mantendo últimos $RETENTION)."
        fi
    fi

    size="$(du -h "$final_file" | cut -f1)"
    success "Backup concluído: $final_file ($size)"
}

main "$@"
