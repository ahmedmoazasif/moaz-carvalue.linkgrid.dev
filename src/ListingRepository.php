<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * Fetches listings from the database by year/make/model with optional mileage band.
 * Listings without listing_price are excluded from results (design-doc §5.2 #8).
 */
final class ListingRepository
{
    private const SAMPLE_LIMIT = 100;
    /** Mileage band: ±25% of user mileage, or ±25,000 miles, whichever is smaller (design-doc §3.1). */
    private const MILEAGE_BAND_PCT = 0.25;
    private const MILEAGE_BAND_ABS = 25000;

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Find listings matching year, make, model. Only rows with non-null listing_price are included.
     * When $mileage is provided, results are restricted to a mileage band (±25% or ±25k miles).
     * Average is computed over all matching rows; sample is capped at SAMPLE_LIMIT.
     *
     * @return array{listings: array<int, array>, total_count: int, average_price: float|null}
     */
    public function findByYearMakeModel(int $year, string $make, string $model, ?int $mileage = null): array
    {
        $make = trim($make);
        $model = trim($model);
        if ($make === '' || $model === '') {
            return ['listings' => [], 'total_count' => 0, 'average_price' => null];
        }

        $params = [
            'year'  => $year,
            'make'  => $make,
            'model' => $model,
        ];

        $where = "year = :year AND make = :make AND model = :model AND listing_price IS NOT NULL";
        if ($mileage !== null && $mileage > 0) {
            $band = $this->mileageBand($mileage);
            $where .= " AND listing_mileage IS NOT NULL
                       AND listing_mileage >= :mileage_min AND listing_mileage <= :mileage_max";
            $params['mileage_min'] = $band['min'];
            $params['mileage_max'] = $band['max'];
        }

        // Aggregate over all matching rows for correct average
        $aggSql = "SELECT COUNT(*) AS cnt, AVG(listing_price) AS avg_price FROM listings WHERE $where";
        $aggStmt = $this->pdo->prepare($aggSql);
        $aggStmt->execute($params);
        $agg = $aggStmt->fetch(PDO::FETCH_ASSOC);
        $totalCount = (int) ($agg['cnt'] ?? 0);
        $averagePrice = $agg['avg_price'] !== null ? (float) $agg['avg_price'] : null;

        // Sample up to SAMPLE_LIMIT for display
        $sampleSql = "
            SELECT id, year, make, model, trim, listing_price, listing_mileage, dealer_city, dealer_state
            FROM listings
            WHERE $where
            ORDER BY listing_price ASC
            LIMIT " . self::SAMPLE_LIMIT;
        $stmt = $this->pdo->prepare($sampleSql);
        $stmt->execute($params);
        $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['listings' => $listings, 'total_count' => $totalCount, 'average_price' => $averagePrice];
    }

    /**
     * Mileage band: ±25% or ±25,000 miles, whichever is smaller.
     */
    private function mileageBand(int $mileage): array
    {
        $deltaPct = (int) ceil($mileage * self::MILEAGE_BAND_PCT);
        $delta = min($deltaPct, self::MILEAGE_BAND_ABS);
        return [
            'min' => max(0, $mileage - $delta),
            'max' => $mileage + $delta,
        ];
    }
}
