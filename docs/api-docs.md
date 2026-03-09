# CarValue API Documentation

This document describes the JSON API layer for estimating vehicle market value (design-doc §5.1).

## Base URL

- **Local:** `http://localhost:8000/api.php`
- **Production:** `http://moaz-carvalue.linkgrid.dev/api.php`

## Endpoint

### GET — Market value estimate

Returns an estimated market value and sample listings for a given year, make, and model. Optional mileage filters results to a mileage band for a more relevant average.

**URL:** `GET /api.php`

**Query parameters:**

| Parameter | Required | Type   | Description |
|-----------|----------|--------|-------------|
| `year`    | Yes      | integer| Vehicle model year (1900–2100). |
| `make`    | Yes      | string | Make (e.g. Toyota, Honda). |
| `model`   | Yes      | string | Model (e.g. Camry, Civic). |
| `mileage` | No       | integer| Odometer mileage. When provided, only listings within a mileage band (±25% or ±25,000 miles) are used for the estimate. |

**Example (no mileage):**

```
GET /api.php?year=2015&make=Toyota&model=Camry
```

**Example (with mileage):**

```
GET /api.php?year=2015&make=Toyota&model=Camry&mileage=150000
```

---

## Responses

### 200 OK — Success

**When sufficient data exists (≥ 5 comparable listings with price):**

- `estimated_value` (integer): Average market value rounded to the nearest hundred (e.g. 13800).
- `sample_listings` (array): Up to 100 sample listings used for the estimate.
- `total_matches` (integer): Total number of listings that matched (with non-null price, and mileage band if mileage was given).
- `message` (null): Omitted or null.

**When insufficient or no data:**

- `estimated_value` (null): No estimate.
- `sample_listings` (array): Empty or partial list.
- `total_matches` (integer): 0 or &lt; 5.
- `message` (string): `"No listings found."` or `"Insufficient data."`.

**Sample listing object:**

Each item in `sample_listings` has:

| Field     | Type           | Description |
|-----------|----------------|-------------|
| `vehicle` | string         | Year, make, model, and trim (e.g. "2015 Toyota Camry LE"). |
| `price`   | number or null | Listing price. |
| `mileage` | integer or null| Odometer mileage. |
| `location`| string or null | City and state (e.g. "Seattle, WA"). |

**Example response (200, with estimate):**

```json
{
    "estimated_value": 12000,
    "sample_listings": [
        {
            "vehicle": "2015 Toyota Camry CE",
            "price": 10000,
            "mileage": 50000,
            "location": "Seattle, WA"
        }
    ],
    "total_matches": 5,
    "message": null
}
```

**Example response (200, no data):**

```json
{
    "estimated_value": null,
    "sample_listings": [],
    "total_matches": 0,
    "message": "No listings found."
}
```

---

### 400 Bad Request — Validation error

Returned when required parameters are missing or invalid (e.g. non-numeric year, missing make/model).

**Example:**

```json
{
    "error": "Validation failed.",
    "details": ["year is required", "make is required"]
}
```

**Common validation rules:**

- `year`: Required; must be numeric and between 1900 and 2100.
- `make`: Required; must be non-empty after trim.
- `model`: Required; must be non-empty after trim.
- `mileage`: Optional; if present, must be a non-negative integer.

---

## Behavior summary

| Topic | Behavior |
|-------|----------|
| **Estimate** | Average of `listing_price` over matching rows, rounded to nearest 100. |
| **Mileage band** | When `mileage` is given: only listings with `listing_mileage` within ±25% or ±25,000 miles (whichever is smaller) are included. |
| **Null prices** | Listings without `listing_price` are excluded from the average and from `sample_listings`. |
| **Minimum sample size** | At least 5 comparable listings (with price) are required for an estimate; otherwise `estimated_value` is null and `message` indicates insufficient or no data. |
| **Sample cap** | `sample_listings` contains at most 100 items; `total_matches` can be greater. |

---

## Integration tests

The following cases are covered by integration tests (design-doc §5.2):

1. Search by year/make/model only — 200, numeric `estimated_value`, `sample_listings` array, value = average rounded to nearest hundred.
2. Search with mileage — response reflects mileage band (fewer matches or different average when applicable).
3. No matches — 200, empty/zero estimate, empty `sample_listings`, and a `message`.
4. Missing required params — 400.
5. Invalid params (e.g. non-numeric year) — 400.
6. Sample listing shape — each item has vehicle, price, mileage, and location.
7. Sample cap — `sample_listings` length ≤ 100.
8. Listings with null price — excluded from average and from sample list.

Run tests (with test DB and fixtures):

```bash
composer install
vendor/bin/phpunit
```

Environment variables for tests: `MYSQL_DATABASE=moaz_carvalue_test` (and optional `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_USER`, `MYSQL_PASSWORD`) as in `phpunit.xml`.
