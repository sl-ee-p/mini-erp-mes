#!/usr/bin/env bash
# Deploy one pair of image tags with docker compose; roll back to the previous pair if it never gets healthy.
# Usage: APP_IMAGE=ghcr.io/owner/repo:<sha> WEB_IMAGE=ghcr.io/owner/repo-web:<sha> deploy/deploy.sh
# Runs on the self-hosted runner. The app dir holds the real .env, DB backups and the deployed-tag record.
set -euo pipefail

: "${APP_IMAGE:?APP_IMAGE must be set}"
: "${WEB_IMAGE:?WEB_IMAGE must be set}"
APP_DIR="${APP_DIR:-/srv/apps/mini-erp-mes}"
HEALTH_URL="${HEALTH_URL:-http://127.0.0.1:8081/up}"
KEEP_BACKUPS="${KEEP_BACKUPS:-10}"
SRC_DIR="$(cd "$(dirname "$0")" && pwd)"

cd "$APP_DIR"
[ -f .env ] || { echo "Missing $APP_DIR/.env (create it from .env.example first)" >&2; exit 1; }
cp "$SRC_DIR/compose.prod.yml" compose.prod.yml

compose() { docker compose -f compose.prod.yml "$@"; }

healthy() {
  for _ in $(seq 1 60); do
    curl -fsS "$HEALTH_URL" >/dev/null 2>&1 && return 0
    sleep 3
  done
  return 1
}

PREVIOUS_APP="$(sed -n 1p .deployed-images 2>/dev/null || true)"
PREVIOUS_WEB="$(sed -n 2p .deployed-images 2>/dev/null || true)"

# Back up the database before the new image can migrate it. Skipped on the very first deploy.
if [ -n "$PREVIOUS_APP" ] && compose ps --status running --services 2>/dev/null | grep -qx mysql; then
  mkdir -p backups
  # shellcheck disable=SC2016  # the password is expanded inside the container, not here
  compose exec -T mysql sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines "$MYSQL_DATABASE"' \
    | gzip > "backups/pre-deploy-$(date +%Y%m%d-%H%M%S).sql.gz"
  ls -1t backups/pre-deploy-*.sql.gz | tail -n +"$((KEEP_BACKUPS + 1))" | xargs -r rm --
fi

echo "Deploying $APP_IMAGE (previous: ${PREVIOUS_APP:-none})"
export APP_IMAGE WEB_IMAGE
compose pull app nginx
compose up -d mysql --wait
compose run --rm --no-deps app php artisan migrate --force
compose up -d --remove-orphans

if healthy; then
  printf '%s\n%s\n' "$APP_IMAGE" "$WEB_IMAGE" > .deployed-images
  docker image prune -f >/dev/null
  echo "Deployed $APP_IMAGE"
  exit 0
fi

echo "Health check failed for $APP_IMAGE" >&2
compose logs --tail=50 app nginx >&2 || true
if [ -n "$PREVIOUS_APP" ] && [ -n "$PREVIOUS_WEB" ]; then
  echo "Rolling back to $PREVIOUS_APP (migrations are not reverted; restore from backups/ if needed)" >&2
  APP_IMAGE="$PREVIOUS_APP" WEB_IMAGE="$PREVIOUS_WEB" compose up -d --remove-orphans
  healthy && echo "Rollback is healthy" >&2 || echo "Rollback is NOT healthy either" >&2
fi
exit 1
