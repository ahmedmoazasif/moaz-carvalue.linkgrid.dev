#!/usr/bin/env bash
#
# Run PHPUnit integration tests (design-doc §5.2).
# Requires: PHP 8+, Composer, and MySQL/MariaDB (test DB moaz_carvalue_test is created by the test bootstrap).
# Set MYSQL_HOST, MYSQL_PORT, MYSQL_USER, MYSQL_PASSWORD if needed (defaults: localhost, 3306, root, empty).

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

if ! command -v php &>/dev/null; then
  echo "PHP not found. Install PHP 8+ to run tests." >&2
  exit 1
fi

if ! command -v composer &>/dev/null; then
  echo "Composer not found. Install Composer (https://getcomposer.org) to run tests." >&2
  exit 1
fi

composer install -q
exec vendor/bin/phpunit "$@"
