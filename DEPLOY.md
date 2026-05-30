# Deploy AYA LodgeOS no Coolify

## Stack

- Laravel 11
- Filament 3.3
- Inertia + React
- PostgreSQL
- Redis
- Dockerfile proprio com PHP 8.3, Nginx, PHP-FPM, `intl`, `pdo_pgsql` e Redis extension

## Variaveis no Coolify

Definir na aplicacao Laravel:

```env
APP_NAME="AYA LodgeOS"
APP_ENV=production
APP_KEY=base64:GERAR_COM_ARTISAN_KEY_GENERATE
APP_DEBUG=false
APP_URL=https://app.seudominio.com
APP_TIMEZONE=Africa/Maputo
APP_LOCALE=pt_PT
APP_FALLBACK_LOCALE=en

DB_CONNECTION=pgsql
DB_HOST=x5a6wrt9yz379h2bu0p2furr
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=COPIAR_DO_COOLIFY

REDIS_CLIENT=phpredis
REDIS_HOST=xgosvmmzlaui8dqg9liilope
REDIS_PASSWORD=null
REDIS_PORT=6379

SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis

RUN_MIGRATIONS=true
```

## Porta da aplicacao

O container expoe a porta:

```text
8080
```

No Coolify, a aplicacao deve apontar para a porta interna `8080`.

## Primeiro admin Filament

Depois do primeiro deploy, criar o utilizador admin:

```bash
php artisan make:filament-user
```

No Coolify, isto pode ser executado no terminal da aplicacao.
