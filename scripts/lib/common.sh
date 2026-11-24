#!/usr/bin/env bash
#
# scripts/lib/common.sh — Funções compartilhadas pelos scripts do projeto.
# Este arquivo é carregado com "source"; não execute diretamente.

# ---------------------------------------------------------------------------
# Saída formatada
# ---------------------------------------------------------------------------
step()    { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
info()    { printf '    %s\n' "$*"; }
success() { printf '\n\033[1;32m✔ %s\033[0m\n' "$*"; }
fail()    { printf '\n\033[1;31m✖ %s\033[0m\n' "$*" >&2; exit 1; }

# ---------------------------------------------------------------------------
# Pré-requisitos
# ---------------------------------------------------------------------------
require_commands() {
    local cmd
    for cmd in "$@"; do
        command -v "$cmd" >/dev/null 2>&1 || fail "Comando '$cmd' não encontrado. Instale-o e tente novamente."
    done
}

# ---------------------------------------------------------------------------
# Arquivos .env
# ---------------------------------------------------------------------------

# env_get CHAVE ARQUIVO -> imprime o valor (sem aspas) ou vazio
env_get() {
    local key="$1" file="$2" value
    [[ -f "$file" ]] || return 0
    value="$(grep -E "^${key}=" "$file" | tail -n 1 | cut -d '=' -f 2- || true)"
    value="${value%\"}"; value="${value#\"}"
    printf '%s' "$value"
}

# env_set CHAVE VALOR ARQUIVO -> substitui a linha (ou adiciona no fim)
env_set() {
    local key="$1" value="$2" file="$3" tmp
    tmp="$(mktemp)"
    if grep -qE "^#? ?${key}=" "$file"; then
        awk -v k="$key" -v v="$value" '
            !done && $0 ~ "^#? ?" k "=" { print k "=" v; done = 1; next }
            { print }
        ' "$file" > "$tmp"
    else
        cat "$file" > "$tmp"
        printf '%s=%s\n' "$key" "$value" >> "$tmp"
    fi
    cat "$tmp" > "$file"
    rm -f "$tmp"
}

random_password() {
    if command -v openssl >/dev/null 2>&1; then
        openssl rand -hex 16
    else
        head -c 16 /dev/urandom | od -An -tx1 | tr -d ' \n'
    fi
}

# ---------------------------------------------------------------------------
# Docker / PostgreSQL
# ---------------------------------------------------------------------------
container_exists()  { [[ -n "$(docker ps -aq -f "name=^${1}$")" ]]; }
container_running() { [[ -n "$(docker ps -q -f "name=^${1}$")" ]]; }

# wait_for_postgres CONTAINER USUARIO BANCO [TIMEOUT_SEGUNDOS]
# Usa -h 127.0.0.1 para só considerar pronto quando o servidor DEFINITIVO
# estiver aceitando TCP (o servidor temporário da inicialização usa só socket).
wait_for_postgres() {
    local container="$1" user="$2" db="$3" timeout="${4:-60}" elapsed=0
    info "Aguardando o PostgreSQL ficar pronto..."
    until docker exec "$container" pg_isready -h 127.0.0.1 -U "$user" -d "$db" >/dev/null 2>&1; do
        sleep 1
        elapsed=$((elapsed + 1))
        if (( elapsed >= timeout )); then
            docker logs --tail 20 "$container" >&2 || true
            fail "PostgreSQL não respondeu em ${timeout}s."
        fi
    done
    info "PostgreSQL pronto (${elapsed}s)"
}
