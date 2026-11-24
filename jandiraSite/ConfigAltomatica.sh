
#!/bin/bash

# Interrompe a execução se algum comando falhar
set -e

echo "=================================="
echo " Iniciando configuração do projeto"
echo "=================================="

# 1. Instalar dependências PHP
echo "[1/7] Instalando dependências PHP..."
composer install

# 2. Instalar dependências Node.js
echo "[2/7] Instalando dependências Node.js..."
npm ci

# 3. Compilar os arquivos frontend
echo "[3/7] Compilando frontend..."
npm run build

# 4. Gerar chave da aplicação Laravel
echo "[4/7] Gerando chave da aplicação..."
php artisan key:generate

# 5. Criar diretório de dados do PostgreSQL
echo "[5/7] Preparando PostgreSQL..."
mkdir -p ./docker/postgresql/data

# Verificar se o container já existe
if [ "$(docker ps -aq -f name=^postgres$)" ]; then
    echo "Container postgres já existe."
else
    docker run -d \
      --name postgres \
      --restart unless-stopped \
      -p 5432:5432 \
      -v "$(pwd)/docker/postgresql/data:/var/lib/postgresql/data" \
      -e POSTGRES_USER=postgres \
      -e POSTGRES_PASSWORD=strongpassword \
      -e POSTGRES_DB=jandiraDB \
      postgres:15.4-alpine
fi

# 6. Executar migrations
echo "[6/7] Executando migrations..."
php artisan migrate

# 7. Executar seeders
echo "[7/7] Executando seeders..."
php artisan migrate --seed

echo "=================================="
echo " Configuração concluída!"
echo "=================================="
