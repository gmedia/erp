#!/bin/sh
set -eu

STACK_NAME="${STACK_NAME:-erp}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.dist.yml}"
ENV_FILE="${ENV_FILE:-.env.production}"
NETWORK_NAME="${NETWORK_NAME:-erp-network}"

for name in APP_IMAGE GHCR_USERNAME GHCR_TOKEN; do
	eval "value=\${$name:-}"
	if [ -z "$value" ]; then
		echo "Missing required environment variable: $name" >&2
		exit 1
	fi
done

if [ ! -f "$COMPOSE_FILE" ]; then
	echo "Compose file not found: $COMPOSE_FILE" >&2
	exit 1
fi

if [ ! -f "$ENV_FILE" ]; then
	echo "Environment file not found: $ENV_FILE" >&2
	exit 1
fi

echo "Logging in to GHCR..."
printf '%s\n' "$GHCR_TOKEN" | docker login ghcr.io -u "$GHCR_USERNAME" --password-stdin

if ! docker network inspect "$NETWORK_NAME" > /dev/null 2>&1; then
	echo "Creating overlay network: $NETWORK_NAME"
	docker network create --driver overlay --attachable "$NETWORK_NAME"
fi

echo "Pulling application image: $APP_IMAGE"
docker pull "$APP_IMAGE"

echo "Running database migrations..."
docker run --rm \
	--env-file "$ENV_FILE" \
	--network "$NETWORK_NAME" \
	"$APP_IMAGE" php artisan migrate --force

echo "Deploying stack: $STACK_NAME"
APP_IMAGE="$APP_IMAGE" RUN_MIGRATIONS=false docker stack deploy --with-registry-auth -c "$COMPOSE_FILE" "$STACK_NAME"

echo "Deployment finished."
