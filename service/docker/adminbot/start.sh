#!/bin/sh
set -e

cleanup() {
  echo "[adminbot] Removing admin user $ADMIN_USER..."
  psql "$DATABASE_URL" -c \
    "DELETE FROM users WHERE username='$ADMIN_USER';"
}
trap cleanup EXIT TERM INT

ADMIN_USER="admin_$(openssl rand -hex 3)"
ADMIN_PASS="$(openssl rand -base64 9)"

echo "[adminbot] Creating admin $ADMIN_USER / $ADMIN_PASS"
psql "$DATABASE_URL" -c \
  "INSERT INTO users(username, password, is_admin) \
   VALUES('$ADMIN_USER', crypt('$ADMIN_PASS', gen_salt('bf')), true);"

export ADMIN_USER ADMIN_PASS APP_URL

while true; do
  echo "[adminbot] Running adminbot at $(date)"
  node /usr/src/app/adminbot.js || echo "[adminbot] Worker error"
  sleep 60
done