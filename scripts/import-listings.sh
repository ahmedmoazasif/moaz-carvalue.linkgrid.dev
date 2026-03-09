#!/usr/bin/env bash
#
# CarValue: download and import pipe-separated (PSV) market data into MySQL/MariaDB.
# Uses LOAD DATA LOCAL INFILE for streaming import; keeps process memory under 500MB.
#
# Usage:
#   ./scripts/import-listings.sh [DATA_FILE]
#   If DATA_FILE is omitted, downloads the full file from the project URL.
#
# Env (optional): MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
DATA_URL="${DATA_URL:-https://linkgrid.com/downloads/carvalue_project/inventory-listing-2022-08-17.txt}"
WORK_DIR="${WORK_DIR:-$REPO_ROOT}"
DATA_FILE_ARG="${1:-}"

# MySQL/MariaDB connection (defaults match README local env)
MYSQL_HOST="${MYSQL_HOST:-localhost}"
MYSQL_PORT="${MYSQL_PORT:-3306}"
MYSQL_USER="${MYSQL_USER:-root}"
MYSQL_PASSWORD="${MYSQL_PASSWORD:-}"
MYSQL_DATABASE="${MYSQL_DATABASE:-moaz-carvalue}"

MYSQL_OPTS=(-h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER")
[[ -n "$MYSQL_PASSWORD" ]] && MYSQL_OPTS+=(-p"$MYSQL_PASSWORD")
# Required for LOAD DATA LOCAL INFILE
MYSQL_OPTS+=(--local-infile=1)

log() { printf '[%s] %s\n' "$(date +%H:%M:%S)" "$*" >&2; }

if ! command -v mysql &>/dev/null; then
  log "Error: mysql client not found. Install MariaDB/MySQL client or run inside a container with mysql."
  exit 1
fi

# Resolve path to data file (streaming download or use provided path)
resolve_data_file() {
  if [[ -n "$DATA_FILE_ARG" ]]; then
    if [[ -f "$DATA_FILE_ARG" ]]; then
      echo "$(cd "$(dirname "$DATA_FILE_ARG")" && pwd)/$(basename "$DATA_FILE_ARG")"
      return
    fi
    if [[ -f "$REPO_ROOT/$DATA_FILE_ARG" ]]; then
      echo "$REPO_ROOT/$DATA_FILE_ARG"
      return
    fi
    log "Error: Data file not found: $DATA_FILE_ARG"
    exit 1
  fi

  local dest="$WORK_DIR/inventory-listing-2022-08-17.txt"
  if [[ -f "$dest" ]]; then
    log "Using existing file: $dest"
    echo "$dest"
    return
  fi

  log "Downloading data file (streaming to disk, progress below)..."
  curl -# -L -o "$dest" "$DATA_URL"
  if [[ ! -f "$dest" ]]; then
    log "Error: Download failed."
    exit 1
  fi
  log "Download complete: $dest"
  echo "$dest"
}

DATA_FILE="$(resolve_data_file)"
if [[ ! -f "$DATA_FILE" ]]; then
  log "Error: Data file missing: $DATA_FILE"
  exit 1
fi

log "Creating database and tables..."
mysql "${MYSQL_OPTS[@]}" < "$SCRIPT_DIR/schema.sql"
log "Schema applied."

log "Importing data (LOAD DATA LOCAL INFILE; may take several minutes)..."
# Substitute @DATA_FILE@ with actual path (escape for sed replacement)
DATA_FILE_ESC="${DATA_FILE//\\/\\\\}"
DATA_FILE_ESC="${DATA_FILE_ESC//&/\\&}"
LOAD_SQL="$(sed "s|@DATA_FILE@|$DATA_FILE_ESC|g" "$SCRIPT_DIR/load-data.sql")"
printf '%s' "$LOAD_SQL" | mysql "${MYSQL_OPTS[@]}"
log "Import finished."

log "Removing invalid rows (missing year/make/model)..."
mysql "${MYSQL_OPTS[@]}" < "$SCRIPT_DIR/post-load-cleanup.sql"
log "Cleanup done."

log "Validating import..."
ROWS=$(mysql "${MYSQL_OPTS[@]}" -N -e "SELECT COUNT(*) FROM \`$MYSQL_DATABASE\`.listings;")
log "Total rows in listings: $ROWS"

SAMPLE=$(mysql "${MYSQL_OPTS[@]}" -e "SELECT id, year, make, model, listing_price, listing_mileage FROM \`$MYSQL_DATABASE\`.listings LIMIT 3;" 2>/dev/null || true)
if [[ -n "$SAMPLE" ]]; then
  log "Sample rows:"
  echo "$SAMPLE" | sed 's/^/  /' >&2
fi

log "Done. Listings imported and validated."
