# Realtime Queue Core

Laravel 13 backend with Laravel Passport, plus a Nuxt 4 + Nuxt UI frontend.

## Security

This project uses an `access_token` + `refresh_token` model. The refresh token is stored in an HttpOnly cookie so the frontend never touches it in JavaScript. The full flow (login, refresh, revoke, and environment requirements) is documented in `AUTH_REFRESH_TOKEN_SETUP.md`.

## Requirements

- PHP 8.3+
- Composer
- Node.js 20+
- MySQL 8+

## Setup After Clone

### 1. Clone the project

```bash
git clone <your-repo-url>
cd laravel-queue-project
```

### 2. Install backend dependencies

```bash
composer install
```

### 3. Create the Laravel environment file

```bash
copy .env.example .env
```

macOS/Linux:

```bash
cp .env.example .env
```

### 4. Update Laravel `.env`

At minimum, set the following:

```env
APP_NAME="Realtime Queue Core"
APP_ENV=local
APP_KEY=
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

PASSPORT_PASSWORD_CLIENT_ID=
PASSPORT_PASSWORD_CLIENT_SECRET=
```

### 5. Generate the app key

```bash
php artisan key:generate
```

### 6. Create the database and run migrations

Create a MySQL database named `laravel_queue_project`, then run:

```bash
php artisan migrate
```

### 7. Create the Passport password grant client

```bash
php artisan passport:client --password --name="Nuxt Password Grant" --provider=users
```

Copy the generated values into:

- `PASSPORT_PASSWORD_CLIENT_ID`
- `PASSPORT_PASSWORD_CLIENT_SECRET`

After updating `.env`, clear config:

```bash
php artisan config:clear
```

### 8. Seed the admin account

```bash
php artisan db:seed
```

Default credentials:

- Email: `admin@gmail.com`
- Password: `12345678`

### 9. Install frontend dependencies

```bash
cd frontend
npm install
```

### 10. Create the frontend environment file

Create `frontend/.env`:

```env
NUXT_PUBLIC_API_BASE=http://127.0.0.1:8000/api
```

### 11. Run the project

Open two terminals.

Backend:

```bash
php artisan serve
```

Frontend:

```bash
cd frontend
npm run dev
```

Access:

- Frontend: `http://localhost:3000`
- Backend: `http://127.0.0.1:8000`

## Frontend Auth Notes

- The access token is stored only in Pinia state (memory).
- On page reload, the app restores the session by calling `POST /api/auth/refresh-token` using the HttpOnly refresh cookie.

## Useful Commands

```bash
php artisan test
php artisan migrate:fresh --seed
```

```bash
cd frontend
npm run typecheck
```

## Notes

- If login returns `Unable to issue tokens.`, verify `PASSPORT_PASSWORD_CLIENT_ID` and `PASSPORT_PASSWORD_CLIENT_SECRET`.
- After editing `.env`, run `php artisan config:clear`.
- The users management page is restricted to accounts with `role=admin`.
