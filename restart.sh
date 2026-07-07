#!/usr/bin/env sh
set -eu

cd "$(dirname "$0")"

docker compose down
docker compose up -d --build
