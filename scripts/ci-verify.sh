#!/bin/sh

set -eu

verify_quality() {
    scratch=$(mktemp -d)
    suffix=$(basename "$scratch" | tr -cd '[:alnum:]' | tr '[:upper:]' '[:lower:]')
    project="rin-ci-$suffix"
    image="$project-tools"
    override="$scratch/compose.yaml"

    cat > "$override" <<'YAML'
services:
  app:
    ports: !reset []
YAML

    compose() {
        RIN_LARAVEL_PORT=8080 docker compose --env-file /dev/null \
            --project-name "$project" -f compose.yaml -f "$override" "$@"
    }

    cleanup() {
        compose down --volumes --remove-orphans --rmi local || true
        docker image rm "$image" >/dev/null 2>&1 || true
        rm -f "$override"
        rmdir "$scratch"
    }

    trap cleanup EXIT
    trap 'exit 130' INT
    trap 'exit 143' TERM

    docker build --target build --tag "$image" .
    docker run --rm "$image" sh -ec '
        composer audit --locked
        npm audit --audit-level=low
    '
    docker run --rm --network none "$image" sh -ec '
        cp .env.testing.example .env.testing
        composer validate --no-check-publish
        composer check-platform-reqs
        vendor/bin/pint --test
        vendor/bin/phpstan analyse --debug --no-progress --memory-limit=512M
        vendor/bin/pest
        npm run lint
        npm run format:check
        npm run typecheck
        npm run test:unit
    '

    compose up --build --detach --wait --wait-timeout 120
    RIN_BROWSER_PASSWORD=$(compose exec -T app cat /run/rin/demo-password)
    export RIN_BROWSER_PASSWORD
    app_container=$(compose ps -q app)
    docker run --rm --network "container:$app_container" \
        -e RIN_BROWSER_PASSWORD -e PLAYWRIGHT_BASE_URL=http://localhost:8080 -e CI=1 \
        "$image" sh -ec 'npx playwright install --with-deps chromium && npm run test:browser'
}

verify_secrets() {
    export GIT_CONFIG_COUNT=1
    export GIT_CONFIG_KEY_0=safe.directory
    GIT_CONFIG_VALUE_0=$(pwd -P)
    export GIT_CONFIG_VALUE_0

    if [ "$(git rev-parse --is-shallow-repository)" != false ]; then
        printf '%s\n' 'The secret scan requires a full Git checkout.' >&2
        exit 2
    fi

    directory_result=0
    history_result=0
    gitleaks dir --no-banner --redact=100 . || directory_result=$?
    gitleaks git --no-banner --redact=100 --log-opts=--all . || history_result=$?

    if [ "$directory_result" -ne 0 ]; then
        return "$directory_result"
    fi
    return "$history_result"
}

case "${1:-}" in
    quality) verify_quality ;;
    secrets) verify_secrets ;;
    *)
        printf '%s\n' 'Usage: sh scripts/ci-verify.sh quality|secrets' >&2
        exit 2
        ;;
esac
