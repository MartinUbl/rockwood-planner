#!/usr/bin/env sh
set -eu

cd "$(dirname "$0")"

compose() {
	if docker compose version >/dev/null 2>&1; then
		docker compose "$@"
	elif command -v docker-compose >/dev/null 2>&1; then
		docker-compose "$@"
	else
		echo "Docker Compose is not installed. Install docker-compose-plugin or docker-compose." >&2
		exit 1
	fi
}

prepare_runtime_dirs() {
	mkdir -p log temp/sessions www/uploads/issues
	chmod -R a+rwX log temp www/uploads
}

run_migrations() {
	compose exec -T web vendor/bin/phinx migrate
}

compose down --remove-orphans
prepare_runtime_dirs
compose build --pull --no-cache web
compose up -d --force-recreate web
run_migrations
