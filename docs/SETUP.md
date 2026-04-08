# Setup Guide

This guide covers Docker (recommended) and manual local setup.

## Docker (Recommended)

### Requirements

- Docker Desktop

### Start

```bash
docker compose up -d --build
```

### Access

- Frontend: `http://localhost:3000`
- Backend: `http://localhost:8000`
- Soketi WS: `ws://localhost:6001`

### Notes

- MySQL host port: `3307` (container `3306`)
- Database name: `laravel_queue_project`
- Admin seed runs by default
- Passport password client auto-generated and stored in `docker/.env.backend`

## Local (Manual)

### Requirements

- PHP 8.4+
- Composer
- Node.js 20+
- MySQL 8+
- Redis

### Backend

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Update `.env` at minimum:

```env
APP_NAME="Realtime Queue Core"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_queue_project
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SAME_SITE=lax
SESSION_SECURE_COOKIE=false

CORS_ALLOWED_ORIGINS=http://localhost:3000,http://127.0.0.1:3000

BROADCAST_CONNECTION=pusher
QUEUE_CONNECTION=redis
CACHE_STORE=redis

PUSHER_APP_ID=local
PUSHER_APP_KEY=local
PUSHER_APP_SECRET=local
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http

PASSPORT_PASSWORD_CLIENT_ID=
PASSPORT_PASSWORD_CLIENT_SECRET=
```

### DB + Redis + Soketi

Start MySQL + Redis locally, then run Soketi:

```bash
docker run --rm -p 6001:6001 -e SOKETI_DEBUG=1 -e SOKETI_DEFAULT_APP_ID=local -e SOKETI_DEFAULT_APP_KEY=local -e SOKETI_DEFAULT_APP_SECRET=local quay.io/soketi/soketi:latest
```

### Migrate + Passport

```bash
php artisan migrate
php artisan passport:client --password --name="Nuxt Password Grant" --provider=users
php artisan config:clear
php artisan db:seed
```

Default admin:

- Email: `admin@gmail.com`
- Password: `12345678`

### Frontend

```bash
cd frontend
pnpm install
```

Create `frontend/.env`:

```env
NUXT_PUBLIC_API_BASE=http://127.0.0.1:8000/api
```

### Run

Backend:

```bash
php artisan serve
php artisan queue:work
```

Frontend:

```bash
cd frontend
pnpm dev
```

## Frontend Auth Notes

- Access token is stored in Pinia state (memory).
- On reload, the app calls `POST /api/auth/refresh-token` using the HttpOnly refresh cookie.

## Useful Commands

```bash
php artisan test
php artisan migrate:fresh --seed
```

```bash
cd frontend
pnpm typecheck
```

## Troubleshooting

- Passport client not configured
  Fix: set `PASSPORT_PASSWORD_CLIENT_ID` and `PASSPORT_PASSWORD_CLIENT_SECRET` in your env file.
  Docker: edit `docker/.env.backend`, then run:
  `docker compose restart app queue`
  `docker compose exec -T app php artisan config:clear`

- Passport key permission error
  Error: `Key file "file:///var/www/storage/oauth-private.key" permissions are not correct`
  Fix:
  `docker compose exec -T app sh -lc "chmod 600 storage/oauth-private.key storage/oauth-public.key"`

- MySQL port already in use
  Fix: change host port in `docker-compose.yml` (default is `3307:3306`) or stop local MySQL.

- Backend connects to 127.0.0.1 inside Docker
  Fix: ensure container uses `docker/.env.backend` with `DB_HOST=mysql`.

- CORS errors from frontend
  Fix: add `CORS_ALLOWED_ORIGINS=http://localhost:3000,http://127.0.0.1:3000` to backend env and clear config.

- Redis not running
  Fix: start Redis or run `docker compose up -d` to bring up Redis container.
