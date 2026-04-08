#!/usr/bin/env bash
set -e

ENV_FILE="/var/www/.env"
ALT_ENV_FILE="/var/www/docker/.env.backend"
if [ ! -f "$ENV_FILE" ] && [ -f "$ALT_ENV_FILE" ]; then
  ENV_FILE="$ALT_ENV_FILE"
fi

if [ -f "$ENV_FILE" ]; then
  set -a
  # shellcheck disable=SC1090
  . "$ENV_FILE"
  set +a
fi

if [ "${SKIP_COMPOSER_INSTALL}" != "true" ]; then
  if [ ! -f /var/www/vendor/autoload.php ]; then
    composer install
  fi
fi

if [ "${SKIP_DB_BOOTSTRAP}" != "true" ]; then
  php -r "
    \$host = getenv('DB_HOST') ?: 'mysql';
    \$port = getenv('DB_PORT') ?: '3306';
    \$db = getenv('DB_DATABASE') ?: 'laravel_queue_project';
    \$user = getenv('DB_USERNAME') ?: 'root';
    \$pass = getenv('DB_PASSWORD') ?: 'root';
    \$tries = 120;
    while (\$tries > 0) {
      try {
        new PDO('mysql:host=' . \$host . ';port=' . \$port . ';dbname=' . \$db, \$user, \$pass);
        exit(0);
      } catch (Exception \$e) {
        \$tries--;
        usleep(1000000);
      }
    }
    fwrite(STDERR, \"Database not ready\\n\");
    exit(1);
  ";

  if [ -z "${APP_KEY}" ]; then
    php artisan key:generate
  fi

  php artisan migrate --force

  if [ "${RUN_SEED}" = "true" ]; then
    php artisan db:seed --force
  fi

  if [ -z "${PASSPORT_PASSWORD_CLIENT_ID}" ] || [ -z "${PASSPORT_PASSWORD_CLIENT_SECRET}" ]; then
    :
  else
    VALID_PASSPORT_CLIENT=$(php -r "
      \$host = getenv('DB_HOST') ?: 'mysql';
      \$port = getenv('DB_PORT') ?: '3306';
      \$db = getenv('DB_DATABASE') ?: 'laravel_queue_project';
      \$user = getenv('DB_USERNAME') ?: 'root';
      \$pass = getenv('DB_PASSWORD') ?: 'root';
      \$clientId = getenv('PASSPORT_PASSWORD_CLIENT_ID');
      try {
        \$pdo = new PDO('mysql:host=' . \$host . ';port=' . \$port . ';dbname=' . \$db, \$user, \$pass);
        \$stmt = \$pdo->prepare('select count(*) from oauth_clients where id = ?');
        \$stmt->execute([\$clientId]);
        \$count = (int) \$stmt->fetchColumn();
        echo \$count > 0 ? '1' : '0';
      } catch (Exception \$e) {
        echo '0';
      }
    ")
    if [ "$VALID_PASSPORT_CLIENT" != "1" ]; then
      PASSPORT_PASSWORD_CLIENT_ID=""
      PASSPORT_PASSWORD_CLIENT_SECRET=""
    fi
  fi

  if [ -z "${PASSPORT_PASSWORD_CLIENT_ID}" ] || [ -z "${PASSPORT_PASSWORD_CLIENT_SECRET}" ]; then
    OUTPUT=$(php artisan passport:client --password --name="Docker Password Grant" --provider=users --no-interaction)
    CLIENT_ID=$(echo "$OUTPUT" | sed -n "s/.*Client ID[^0-9A-Za-z-]*//p" | head -n 1)
    CLIENT_SECRET=$(echo "$OUTPUT" | sed -n "s/.*Client Secret[^0-9A-Za-z-]*//p" | head -n 1)
    if [ -n "$CLIENT_ID" ] && [ -n "$CLIENT_SECRET" ]; then
      for target in "$ENV_FILE" "$ALT_ENV_FILE"; do
        if [ -f "$target" ]; then
          if grep -q "^PASSPORT_PASSWORD_CLIENT_ID=" "$target"; then
            sed -i "s/^PASSPORT_PASSWORD_CLIENT_ID=.*/PASSPORT_PASSWORD_CLIENT_ID=${CLIENT_ID}/" "$target"
          else
            echo "PASSPORT_PASSWORD_CLIENT_ID=${CLIENT_ID}" >> "$target"
          fi
          if grep -q "^PASSPORT_PASSWORD_CLIENT_SECRET=" "$target"; then
            sed -i "s/^PASSPORT_PASSWORD_CLIENT_SECRET=.*/PASSPORT_PASSWORD_CLIENT_SECRET=${CLIENT_SECRET}/" "$target"
          else
            echo "PASSPORT_PASSWORD_CLIENT_SECRET=${CLIENT_SECRET}" >> "$target"
          fi
        fi
      done
    fi
  fi

  php artisan config:clear
fi

exec "$@"
