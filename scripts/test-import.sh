#!/usr/bin/env bash
#
# Test data import: start MariaDB in Docker, run import with sample data, validate.
# Requires Docker. Usage: ./scripts/test-import.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
CONTAINER_NAME="carvalue-db-test"
SAMPLE_FILE="$REPO_ROOT/docs/sample-data-1000.txt"

if ! command -v docker &>/dev/null; then
  echo "Docker not found. Install Docker or run import against an existing MariaDB." >&2
  exit 1
fi

docker info &>/dev/null || { echo "Docker daemon not running." >&2; exit 1; }

echo "Stopping/removing existing container (if any)..."
docker rm -f "$CONTAINER_NAME" 2>/dev/null || true

echo "Starting MariaDB 10.5..."
docker run -d --name "$CONTAINER_NAME" -p 3307:3306 \
  -e MARIADB_ROOT_PASSWORD=root \
  mariadb:10.5

echo "Waiting for MariaDB to accept connections..."
for i in $(seq 1 30); do
  if docker run --rm --link "$CONTAINER_NAME:db" mariadb:10.5 mysql -h db -u root -proot -e "SELECT 1" &>/dev/null; then
    break
  fi
  sleep 1
done
docker run --rm --link "$CONTAINER_NAME:db" mariadb:10.5 mysql -h db -u root -proot -e "SELECT 1" || { echo "MariaDB did not become ready."; exit 1; }

echo "Running import with sample data..."
# Run import from host if mysql is available; else run inside container (repo mounted).
if command -v mysql &>/dev/null; then
  MYSQL_HOST=127.0.0.1 MYSQL_PORT=3307 MYSQL_USER=root MYSQL_PASSWORD=root "$SCRIPT_DIR/import-listings.sh" "$SAMPLE_FILE"
else
  echo "mysql client not on host. Running import inside container..."
  docker run --rm --link "$CONTAINER_NAME:db" -v "$REPO_ROOT:/repo:ro" -w /repo \
    -e MYSQL_HOST=db -e MYSQL_USER=root -e MYSQL_PASSWORD=root \
    mariadb:10.5 \
    bash -c "/repo/scripts/import-listings.sh /repo/docs/sample-data-1000.txt"
fi

echo "Stopping container..."
docker rm -f "$CONTAINER_NAME" 2>/dev/null || true
echo "Test complete."
