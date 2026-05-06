# Realtime Queue Core

Realtime Queue Core is a full-stack event enrollment platform with realtime updates. It supports authentication and role-based access (admin/user), event management with participant limits, and live attendee tracking. The dashboard shows join/leave activity and capacity signals powered by realtime broadcasting.

**Tech Stack**
- Backend: Laravel 13, PHP 8.3, Laravel Passport (OAuth), Laravel Queue
- Realtime: Soketi (WebSocket), Redis, Laravel Broadcasting
- Database: MySQL 8
- Frontend: Nuxt 4, Nuxt UI, Pinia, Tailwind CSS
- Tooling: Vite, TypeScript

**Highlights**
- Event CRUD with limit and enrollment status
- Realtime attendee count + join/leave timeline
- Admin user management
- Filtering and pagination in admin tables
- Activity logs with role-based visibility (admin sees all, user sees own)
- Login rate limiting (IP + email) and Redis-backed sessions for high-load stability
- Race-condition-safe event joining via database row locking (`lockForUpdate`)
- Secure token-based auth: short-lived access token (Pinia memory) + HttpOnly refresh cookie with rotation

---

## Table of Contents

- [Features](#features)
- [Quick Start (Docker)](#quick-start-docker)
- [Local Setup](#local-setup)
- [Project Structure](#project-structure)
- [Authentication Flow](#authentication-flow)
- [Activity Logs](#activity-logs)
- [Docs](#docs)

---

## Features

| Feature | Description |
|---------|-------------|
| Event Management | Create, update, delete events with title, description, image, start/end time, and participant limit |
| Realtime Dashboard | Live join/leave timeline, capacity progress bar, weekly participation chart, join/leave ratio pie chart |
| Activity Logs | Global log page showing all join/leave actions; admins see everything, users see only their own history |
| Role-based Access | `admin` can manage all users and events; `user` can join/leave events and view personal logs |
| Race Condition Fix | `DB::transaction` + `lockForUpdate()` ensures event limits are never exceeded under concurrent requests |
| Auth Security | Access token stored in Pinia memory only; refresh token in HttpOnly cookie; automatic token refresh on 401 |
| Rate Limiting | Login endpoint protected by IP + email rate limiter with lockout responses |

---

## Quick Start (Docker)

**Requirements**
- Docker Desktop

**Start**
```bash
docker compose up -d --build
```

**Access**
- Frontend: http://localhost:3000
- Backend: http://localhost:8000
- Soketi WS: ws://localhost:6001

**Notes**
- MySQL host port: `3307` (container `3306`)
- Database name: `laravel_queue_project`
- Admin seed runs by default
- Passport password client auto-generated and stored in `docker/.env.backend`

---

## Local Setup

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

---

## Project Structure

```
app/
  Events/               # Broadcasting events
  Http/
    Controllers/        # API controllers
    Middleware/         # Admin middleware
  Jobs/                 # Queue jobs
  Models/               # Eloquent models
  QueryBuilders/        # Reusable query builders
  Services/             # Business logic services
  Support/helpers.php   # Global helper functions
bootstrap/
config/
database/
  factories/
  migrations/
  seeders/
docs/
frontend/
  app/
    components/         # Vue components
    layouts/            # Nuxt layouts
    middleware/         # Auth middleware
    pages/              # Nuxt pages
    stores/             # Pinia stores
  nuxt.config.ts
  package.json
routes/
  api.php
```

---

## Authentication Flow

- Access token is stored in **Pinia state (memory)** only — never in localStorage.
- Laravel Passport issues both access token and refresh token.
- Refresh token is stored in an **HttpOnly cookie** (`realtime-queue-core`) and never exposed to JavaScript.
- On `401`, the frontend automatically calls `POST /api/auth/refresh-token` to rotate tokens and retry the original request.
- On logout, both access and refresh tokens are revoked, and the cookie is expired.

See the full auth setup guide in [AUTH_REFRESH_TOKEN_SETUP.md](docs/AUTH_REFRESH_TOKEN_SETUP.md).

---

## Activity Logs

A dedicated **Logs** page is available from the header navigation.

- **Admins** see all join/leave activity across all events and users.
- **Regular users** see only their own participation history.
- Logs support searching by event title or user name/email, and filtering by action (`join` / `leave`).
- Log creation is handled by a reusable helper `create_event_log()` in `app/Support/helpers.php`.

---

## Docs

- [Setup Guide](docs/SETUP.md) — Docker and manual setup instructions
- [Auth & Refresh Token Setup](docs/AUTH_REFRESH_TOKEN_SETUP.md) — Detailed authentication flow, sequence diagrams, and environment configuration
- [Login + Redis Changes](docs/LOGIN_REDIS_CHANGES.md) — Rate limiting and Redis session storage notes
- [Race Condition Fix](docs/race-condition-fix.md) — How concurrent event joining is prevented with `lockForUpdate()`

---

## Useful Commands

```bash
# Backend
php artisan test
php artisan migrate:fresh --seed
php artisan route:cache

# Frontend
cd frontend
pnpm typecheck
pnpm lint
```

## Troubleshooting

- **Passport client not configured**
  Fix: set `PASSPORT_PASSWORD_CLIENT_ID` and `PASSPORT_PASSWORD_CLIENT_SECRET` in your env file.
  Docker: edit `docker/.env.backend`, then run:
  ```bash
  docker compose restart app queue
  docker compose exec -T app php artisan config:clear
  ```

- **Passport key permission error**
  ```bash
  docker compose exec -T app sh -lc "chmod 600 storage/oauth-private.key storage/oauth-public.key"
  ```

- **MySQL port already in use**
  Fix: change host port in `docker-compose.yml` (default is `3307:3306`) or stop local MySQL.

- **Backend connects to 127.0.0.1 inside Docker**
  Fix: ensure container uses `docker/.env.backend` with `DB_HOST=mysql`.

- **CORS errors from frontend**
  Fix: add `CORS_ALLOWED_ORIGINS=http://localhost:3000,http://127.0.0.1:3000` to backend env and clear config.

- **Redis not running**
  Fix: start Redis or run `docker compose up -d` to bring up Redis container.
