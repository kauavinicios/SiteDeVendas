#!/usr/bin/env bash
#
# scripts/docker.sh — Gerencia o ambiente containerizado (Caddy + PHP-FPM + Worker + Postgres).
#
# Uso:
#   scripts/docker.sh [opções] <comando> [argumentos...]
#
# Opções globais (antes do comando):
#   --prod             Usa docker-compose.prod.yml em vez de docker-compose.dev.yml
#   --with-vite        Ativa o perfil Vite HMR (apenas dev, porta 5173)
#   --with-mail        Ativa o perfil Mailpit (apenas dev, UI em http://127.0.0.1:8025)
#   -h, --help         Exibe esta ajuda
#
# Comandos:
#   up                 Prepara .env (em dev), constrói imagens e sobe o stack aguardando saúde
#   down               Para e remove os containers do stack (mantém volumes de dados)
#   build              Constrói ou reconstrói as imagens do stack
#   restart            Reinicia os containers do stack
#   logs [serviço...]  Acompanha logs em tempo real
#   status | ps        Lista o estado e healthchecks dos containers
#   artisan [args...]  Executa php artisan dentro do container php
#   composer [args...] Executa composer dentro do container php
#   shell              Abre um shell interativo dentro do container php
#   security-check     Executa php artisan app:security-check dentro do container php

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "$SCRIPT_DIR/../jandiraSite" && pwd)"
ENV_FILE="$APP_DIR/.env"

# shellcheck source=scripts/lib/common.sh
source "$SCRIPT_DIR/lib/common.sh"

MODE="dev"
PROFILES=()

usage() {
    sed -n '/^# Uso:/,/^set -euo/p' "${BASH_SOURCE[0]}" | sed 's/^set -euo.*//; s/^# \{0,1\}//'
}

run_compose() {
    local args=()
    if [[ ${#PROFILES[@]} -gt 0 ]]; then
        args+=("${PROFILES[@]}")
    fi
    if [[ "$MODE" == "prod" ]]; then
        compose_prod "${args[@]}" "$@"
    else
        compose_dev "${args[@]}" "$@"
    fi
}

ensure_dev_env() {
    if [[ ! -f "$ENV_FILE" ]]; then
        cp "$APP_DIR/.env.example" "$ENV_FILE"
        info "Criado jandiraSite/.env a partir do .env.example"
    fi

    if [[ -z "$(env_get DB_PASSWORD "$ENV_FILE")" ]]; then
        env_set DB_PASSWORD "$(random_password)" "$ENV_FILE"
        info "DB_PASSWORD vazio — senha aleatória gerada no jandiraSite/.env"
    fi
}

cmd_up() {
    if [[ "$MODE" == "prod" ]]; then
        [[ -f "$ENV_FILE" ]] \
            || fail "jandiraSite/.env não encontrado. Copie .env.production.example para .env e preencha as credenciais."

        step "Subindo stack Docker ($MODE)..."
        run_compose up -d --build --wait
        success "Stack de produção no ar! Para deploy completo com backup e migrações use: scripts/deploy.sh"
        return
    fi

    ensure_dev_env

    step "Subindo banco e PHP-FPM ($MODE)..."
    run_compose up -d --build --wait postgres php

    if [[ -z "$(env_get APP_KEY "$ENV_FILE")" ]]; then
        step "Gerando APP_KEY..."
        run_compose exec -T php php artisan key:generate --ansi
    fi

    step "Executando migrations em desenvolvimento..."
    run_compose exec -T php php artisan migrate --force --ansi

    step "Subindo worker, scheduler e Caddy ($MODE)..."
    run_compose up -d --remove-orphans --wait

    local http_port="${DEV_HTTP_PORT:-8080}"
    success "Stack de desenvolvimento no ar!"
    info "Loja / Admin : http://127.0.0.1:${http_port}"
    info "Healthcheck  : http://127.0.0.1:${http_port}/up"
    if printf '%s\n' "${PROFILES[*]:-}" | grep -q "mail"; then
        info "Mailpit UI   : http://127.0.0.1:${MAILPIT_UI_PORT:-8025}"
    fi
}

main() {
    while [[ $# -gt 0 ]]; do
        case "$1" in
            --prod)
                MODE="prod"
                shift
                ;;
            --with-vite)
                PROFILES+=(--profile vite)
                shift
                ;;
            --with-mail)
                PROFILES+=(--profile mail)
                shift
                ;;
            -h|--help)
                usage
                exit 0
                ;;
            *)
                break
                ;;
        esac
    done

    local cmd="${1:-}"
    if [[ -z "$cmd" ]]; then
        usage
        exit 0
    fi
    shift || true

    case "$cmd" in
        help)
            usage
            exit 0
            ;;
    esac

    require_docker_compose

    if [[ "$MODE" == "prod" && "$cmd" != "up" && ! -f "$ENV_FILE" ]]; then
        fail "jandiraSite/.env não encontrado para o modo --prod."
    fi

    case "$cmd" in
        up)
            cmd_up
            ;;
        down)
            run_compose down "$@"
            ;;
        build)
            run_compose build "$@"
            ;;
        restart)
            run_compose restart "$@"
            ;;
        logs)
            run_compose logs -f --tail=100 "$@"
            ;;
        status|ps)
            run_compose ps "$@"
            ;;
        artisan)
            run_compose exec php php artisan "$@"
            ;;
        composer)
            run_compose exec php composer "$@"
            ;;
        shell)
            run_compose exec php sh
            ;;
        security-check)
            run_compose exec -T php php artisan app:security-check "$@"
            ;;
        *)
            usage
            fail "Comando desconhecido: $cmd"
            ;;
    esac
}

main "$@"
