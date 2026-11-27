#!/usr/bin/env bash
#
# scripts/deploy.sh — Deploy seguro e idempotente para ambiente de PRODUÇÃO.
#
# Uso:
#   scripts/deploy.sh [opções]
#
# Opções:
#   --skip-backup      Pula o backup automático pré-deploy (não recomendado)
#   --no-build         Reutiliza as imagens já construídas sem rebuild
#   --allow-warnings   Não falha o app:security-check em avisos (sem --strict)
#   -h, --help         Exibe esta ajuda

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
APP_DIR="$ROOT_DIR/jandiraSite"
ENV_FILE="$APP_DIR/.env"

# shellcheck source=scripts/lib/common.sh
source "$SCRIPT_DIR/lib/common.sh"

SKIP_BACKUP=0
NO_BUILD=0
ALLOW_WARNINGS=0

usage() {
    sed -n '/^# Uso:/,/^set -euo/p' "${BASH_SOURCE[0]}" | sed 's/^set -euo.*//; s/^# \{0,1\}//'
}

validate_prod_env() {
    local errors=()
    local app_env app_debug app_key db_pass site_addr session_enc driver handle webhook_secret

    app_env="$(env_get APP_ENV "$ENV_FILE")"
    app_debug="$(env_get APP_DEBUG "$ENV_FILE")"
    app_key="$(env_get APP_KEY "$ENV_FILE")"
    db_pass="$(env_get DB_PASSWORD "$ENV_FILE")"
    site_addr="$(env_get SITE_ADDRESS "$ENV_FILE")"
    session_enc="$(env_get SESSION_ENCRYPT "$ENV_FILE")"
    driver="$(env_get INFINITEPAY_DRIVER "$ENV_FILE")"
    handle="$(env_get INFINITEPAY_HANDLE "$ENV_FILE")"
    webhook_secret="$(env_get INFINITEPAY_WEBHOOK_SECRET "$ENV_FILE")"

    [[ "$app_env" == "production" ]] \
        || errors+=("APP_ENV deve ser 'production' (atual: '${app_env:-vazio}').")
    [[ "$app_debug" == "false" ]] \
        || errors+=("APP_DEBUG deve ser 'false' em produção (atual: '${app_debug:-vazio}').")
    [[ ${#app_key} -ge 32 ]] \
        || errors+=("APP_KEY ausente ou muito curto (< 32 caracteres).")
    [[ ${#db_pass} -ge 12 && "$db_pass" != "strongpassword" ]] \
        || errors+=("DB_PASSWORD deve ter ao menos 12 caracteres e não usar senha padrão.")
    [[ -n "$site_addr" ]] \
        || errors+=("SITE_ADDRESS obrigatório (ex.: seudominio.com.br ou :80).")
    [[ "$session_enc" == "true" ]] \
        || errors+=("SESSION_ENCRYPT deve ser 'true'.")

    if [[ "$driver" == "live" ]]; then
        [[ -n "$handle" ]] \
            || errors+=("INFINITEPAY_HANDLE é obrigatório quando INFINITEPAY_DRIVER=live.")
        [[ ${#webhook_secret} -ge 24 ]] \
            || errors+=("INFINITEPAY_WEBHOOK_SECRET deve ter >= 24 caracteres no modo live.")
    fi

    if [[ ${#errors[@]} -gt 0 ]]; then
        local err
        for err in "${errors[@]}"; do
            warn "$err"
        done
        fail "Pré-validação de jandiraSite/.env reprovada (${#errors[@]} erro(s))."
    fi

    info "Variáveis críticas de jandiraSite/.env validadas."
}

main() {
    while [[ $# -gt 0 ]]; do
        case "$1" in
            --skip-backup)
                SKIP_BACKUP=1
                shift
                ;;
            --no-build)
                NO_BUILD=1
                shift
                ;;
            --allow-warnings)
                ALLOW_WARNINGS=1
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

    step "[1/7] Verificando pré-requisitos e .env de produção..."
    require_docker_compose
    [[ -f "$ENV_FILE" ]] \
        || fail "jandiraSite/.env não encontrado. Copie jandiraSite/.env.production.example para jandiraSite/.env."
    validate_prod_env

    step "[2/7] Backup preventivo do banco de dados..."
    if [[ "$SKIP_BACKUP" -eq 1 ]]; then
        warn "Backup ignorado (--skip-backup)."
    elif compose_service_running "prod" "postgres"; then
        "$SCRIPT_DIR/backup-db.sh"
    else
        info "Container 'postgres' ainda não existe — primeiro deploy, sem dados para backup."
    fi

    step "[3/7] Construindo imagens de produção..."
    if [[ "$NO_BUILD" -eq 1 ]]; then
        warn "Build ignorado (--no-build)."
    else
        compose_prod build
    fi

    step "[4/7] Subindo containers de produção..."
    compose_prod up -d --remove-orphans --wait

    step "[5/7] Aplicando migrations, cache otimizado e reinício de workers..."
    compose_prod exec -T php php artisan migrate --force --ansi
    compose_prod exec -T php php artisan optimize --ansi
    compose_prod exec -T php php artisan queue:restart --ansi

    step "[6/7] Verificando postura de segurança (app:security-check)..."
    if [[ "$ALLOW_WARNINGS" -eq 1 ]]; then
        compose_prod exec -T php php artisan app:security-check --ansi
    else
        compose_prod exec -T php php artisan app:security-check --strict --ansi
    fi

    step "[7/7] Verificando saúde final dos serviços..."
    compose_prod ps
    compose_prod exec -T caddy wget -qO- http://127.0.0.1:2020/healthz >/dev/null

    success "Deploy de produção concluído com sucesso!"
}

main "$@"
