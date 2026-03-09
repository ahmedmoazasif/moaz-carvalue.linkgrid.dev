# CarValue - Internal Car Search Interface

## Background

CarValue is an internal web interface for estimating car values based on year/make/model and mileage based on a data file containing reference inventory, dealers, and zip codes.

## References

- Code Repository: https://github.com/ahmedmoazasif/moaz-carvalue.linkgrid.dev
- Project Requirements: [docs/project-requirements.md](docs/project-requirements.md)
- Sample Data File (1000 lines): [docs/sample-data-1000.txt](docs/sample-data-1000.txt)
- Full Input Data File (1.2GB, 4713915 lines): https://linkgrid.com/downloads/carvalue_project/inventory-listing-2022-08-17.txt
- Nginx configuration file: [conf/moaz-carvalue.local.conf](conf/moaz-carvalue.local.conf)
- Design document: [docs/design-doc.md](docs/design-doc.md)

## Data import

A bash script downloads the pipe-separated (PSV) market data and imports it into MariaDB/MySQL using `LOAD DATA LOCAL INFILE` (streaming; stays under ~500MB process memory).

- **Script:** `scripts/import-listings.sh`
- **SQL:** `scripts/schema.sql` (create DB and `listings` table), `scripts/load-data.sql` (PSV load), `scripts/post-load-cleanup.sql` (remove invalid rows).

**Usage:**

```bash
# Download full file and import (default URL)
./scripts/import-listings.sh

# Import from a local file (e.g. sample data)
./scripts/import-listings.sh docs/sample-data-1000.txt
```

**Environment (optional):** `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_DATABASE` (defaults: localhost, 3306, root, empty, moaz-carvalue).

**Test with Docker:**

```bash
# Option A: one-command test (starts MariaDB, runs import with sample data, validates)
./scripts/test-import.sh

# Option B: manual – start MariaDB, then run import
docker run -d --name carvalue-db -p 3306:3306 -e MARIADB_ROOT_PASSWORD=root mariadb:10.5
# When DB is ready:
MYSQL_PASSWORD=root ./scripts/import-listings.sh docs/sample-data-1000.txt
```

If `mysql` is not on the host, `test-import.sh` runs the import inside a container with the repo mounted.

## Local Test Environment

- Docker Image: See Dockerfile
- OS: AlmaLinux 9 (running within WSL 2.5.9.0 on Windows 11)
- Database: MySQL (Ver 15.1 Distrib 10.5.27-MariaDB)
- Database Name: moaz-carvalue
- Language: PHP 8.0.30 via php-fpm
- Web Server: nginx 1.20.1
- Root Folder: /root/workspace/moaz-carvalue.linkgrid.dev
- Public Folder: /root/workspace/moaz-carvalue.linkgrid.dev/public
- Host: localhost (this computer)
- Local Nginx Conf: ./conf/moaz-carvalue.local.conf
- Local URL: http://localhost:8000/

## API and tests

- **API:** `GET /api.php?year=...&make=...&model=...&mileage=...` — see [docs/api-docs.md](docs/api-docs.md).
- **Integration tests:** Use a test database (e.g. `moaz_carvalue_test`). Run:
  ```bash
  composer install
  vendor/bin/phpunit
  ```
  Ensure MySQL/MariaDB is running and credentials in `phpunit.xml` (or env) match your test DB. Alternatively, use the helper script: `./scripts/run-tests.sh`.

## Production Environment

- OS: AlmaLinux 9 (running as public VM)
- Database: MySQL (Ver 15.1 Distrib 10.5.27-MariaDB)
- Database Name: moaz-carvalue
- Language: PHP 8.0.30 via php-fpm
- Web Server: nginx 1.20.1
- SSH: moaz-carvalue (user: root, key: "SSH_KEY" on the Github repository)
- Root Folder: /home/moaz-carvalue.linkgrid.dev
- Public Folder: /home/moaz-carvalue.linkgrid.dev/public
- Production URL: http://moaz-carvalue.linkgrid.dev/
