# Butique da Jandira — Casa & Perfumaria

Monólito Laravel 13 + React 19 (Inertia v3 + Tailwind CSS v4) + PostgreSQL 18 com arquitetura containerizada unificada (**Caddy 2 + PHP-FPM 8.4 + Queue Worker + Scheduler**) para máxima paridade entre desenvolvimento local e produção.

---

## Arquitetura & Stack

- **Servidor Web / Proxy:** Caddy 2 Alpine (`Dockerfile-caddy`, HTTP/2, HTTP/3, compressão `zstd`/`gzip`, bloqueio de dotfiles e `.php` em `/storage/`, TLS automático em produção)
- **Aplicação:** PHP 8.4-FPM Alpine (`Dockerfile-php` multi-stage com `pdo_pgsql`, `gd` [JPEG/PNG/WebP para sanitização EXIF], `opcache`, `intl`, `pcntl`, `bcmath`, `zip`, `exif`)
- **Processamento Assíncrono:** `worker` (`php artisan queue:work`) + `scheduler` (`php artisan schedule:work`) compartilhando a mesma imagem PHP
- **Banco de Dados:** PostgreSQL 18 Alpine
- **Frontend:** React 19, Inertia v3, Tailwind v4, Vite 8 (compilado dentro do build Docker no estágio Node 24)

### Estrutura do Repositório

```text
.
├── jandiraSite/                 # Aplicação Laravel + React/Inertia + Dockerfiles e Compose
│   ├── docker/
│   │   ├── caddy/Caddyfile      # Configuração unificada HTTP local / HTTPS produção
│   │   └── php/                 # php.ini, opcache dev/prod, pool www.conf, entrypoint, healthcheck
│   ├── Dockerfile-php           # Multi-stage (assets, composer_prod, development, production)
│   ├── Dockerfile-caddy         # Multi-stage (assets, development, production)
│   ├── docker-compose.yml       # Stack base (postgres, php, worker, scheduler, caddy)
│   ├── docker-compose.dev.yml   # Override local (bind mount, porta 8080, vite/mailpit opcionais)
│   ├── docker-compose.prod.yml  # Override produção (imagens imutáveis, no-new-privileges, portas 80/443)
│   ├── .env.example             # Template para desenvolvimento
│   └── .env.production.example  # Template seguro para produção
├── scripts/                     # Automação operacional (setup, docker, test-env, deploy, backup/restore)
└── .github/workflows/           # CI (tests.yml e lint.yml com validação Docker)
```

---

## 1. Ambiente Local via Docker (Recomendado)

Sobe exatamente a mesma topologia da produção (Caddy + PHP 8.4 + Worker + Scheduler + PostgreSQL 18) em portas isoladas para não colidir com o ambiente do host:

```bash
# Sobe tudo em background (http://127.0.0.1:8080) e roda as migrations
./scripts/docker.sh up

# Opcional: com caixa de e-mails local Mailpit (http://127.0.0.1:8025) e/ou Vite HMR (:5173)
./scripts/docker.sh --with-mail up
./scripts/docker.sh --with-mail --with-vite up
```

**Comandos do dia a dia:**

```bash
./scripts/docker.sh status              # Estado e healthchecks dos containers
./scripts/docker.sh logs php worker     # Logs em tempo real
./scripts/docker.sh artisan tinker      # Executa php artisan no container
./scripts/docker.sh composer install    # Executa composer no container
./scripts/docker.sh shell               # Shell interativo no container PHP
./scripts/docker.sh security-check      # Verifica postura de segurança
./scripts/docker.sh down                # Para os containers mantendo os dados
```

> **Portas locais no modo Docker:** Loja/Admin em `http://127.0.0.1:8080`, PostgreSQL em `127.0.0.1:5434`, Mailpit em `http://127.0.0.1:8025`.

---

## 2. Ambiente Local no Host (Alternativa sem containers de app)

Requer PHP >= 8.3 (com `pdo_pgsql` e recomendado `gd`), Composer, Node/NPM e Docker apenas para o PostgreSQL:

```bash
./scripts/setup.sh
cd jandiraSite && composer dev
```

---

## 3. Testes Automatizados e Homologação Temporária

O script `scripts/test-env.sh` sobe um PostgreSQL 18 temporário em RAM (`tmpfs`, porta `5433`, container `jandira-db-test`) totalmente isolado do banco de desenvolvimento:

```bash
./scripts/test-env.sh up              # Sobe Postgres temporário, gera .env.homolog e faz seed
./scripts/test-env.sh test            # Roda PHPUnit usando PHP do host
./scripts/test-env.sh test --docker   # Roda PHPUnit dentro da imagem PHP 8.4 (com ext-gd real)
./scripts/test-env.sh serve           # Sobe servidor local de homologação em http://127.0.0.1:8000
./scripts/test-env.sh down            # Derruba e descarta o banco temporário
```

---

## 4. Deploy em Produção (VPS do Zero)

### Primeiro deploy

1. **Prepare a VPS** com Docker Engine + Docker Compose v2 e Git, e aponte os registros DNS (`A`/`AAAA`) do domínio para o IP do servidor (com portas `80` e `443` TCP/UDP liberadas no firewall).
2. **Clone o repositório e configure o `.env` de produção:**
   ```bash
   cp jandiraSite/.env.production.example jandiraSite/.env
   ```
3. **Preencha os segredos obrigatórios em `jandiraSite/.env`:**
   - `APP_KEY`: gere **uma única vez** com `docker run --rm composer:2.8 php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"` (ou `php artisan key:generate --show`). **Nunca troque o `APP_KEY` depois que houver dados criptografados no banco.**
   - `DB_PASSWORD` e `INFINITEPAY_WEBHOOK_SECRET`: gere com `openssl rand -hex 24`.
   - `SITE_ADDRESS`: domínio público (ex.: `butiquedajandira.com.br`) para HTTPS automático pelo Caddy, **ou** `:80` se estiver atrás de Cloudflare Tunnel/proxy reverso externo.
   - `APP_URL`, `TRUSTED_PROXIES=*`, credenciais SMTP (`MAIL_*`), `WHATSAPP_NUMBER`, `INFINITEPAY_DRIVER=live` e `INFINITEPAY_HANDLE`.
4. **Execute o deploy seguro:**
   ```bash
   ./scripts/deploy.sh
   ```

O `deploy.sh` valida o `.env` antes de tocar no ambiente, gera backup automático (quando o banco já existir), constrói as imagens imutáveis, sobe os containers aguardando os healthchecks, executa `migrate --force`, `optimize`, `queue:restart` e exige aprovação em `app:security-check --strict`.

### Checklist pós-deploy (Go-Live)
- Conferir `https://seudominio.com.br/up` respondendo `200 OK` com certificado TLS ativo.
- Confirmar no painel da InfinitePay a URL de webhook `https://seudominio.com.br/webhooks/infinitepay/<INFINITEPAY_WEBHOOK_SECRET>`.
- Verificar que todos os 5 serviços estão `healthy` (`./scripts/docker.sh --prod status`).

---

## 5. Backup e Restauração do Banco

Os backups são gravados em `./backups/` (`chmod 700`, ignorado pelo Git) em formato `.sql.gz` com rotação automática dos últimos 14 arquivos:

```bash
# Gerar backup manual (produção por padrão; use --dev para o Compose local)
./scripts/backup-db.sh

# Restaurar um backup (ativa modo manutenção automaticamente durante a operação)
./scripts/restore-db.sh backups/jandira_jandiraDB_YYYYMMDD_HHMMSS.sql.gz
```

**Sugestão de cron diário na VPS (às 03:00):**
```cron
0 3 * * * cd /caminho/do/SiteDeVendas && ./scripts/backup-db.sh >> /var/log/jandira-backup.log 2>&1
```

---

## 6. Qualidade e CI (GitHub Actions)

Os workflows em [.github/workflows/](.github/workflows) executam automaticamente em `push` e `pull_request` para `develop` e `main`:
- **tests.yml:** PostgreSQL 18, PHP 8.4 (`pdo_pgsql`, `gd`, `intl`, `bcmath`, `pcntl`), build Vite, `app:security-check` e PHPUnit.
- **lint.yml:** `pint --test`, Prettier (`npm run format:check`), ESLint (`npx eslint .`), TypeScript (`npx tsc --noEmit`), validação de `docker compose config` (dev e prod) e build das imagens de produção (`Dockerfile-php` e `Dockerfile-caddy`).
