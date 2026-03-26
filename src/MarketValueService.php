<?php

declare(strict_types=1);

namespace App;

/**
 * Computes market value estimate from listings (average rounded to nearest hundred).
 * Requires at least MIN_LISTINGS for an estimate; otherwise returns "Insufficient data" (design-doc §3.2).
 *
 * When a mileage-band search yields insufficient results, falls back to linear extrapolation
 * using OLS regression on all YMM listings with both price and mileage data.
 */
final class MarketValueService
{
    private const MIN_LISTINGS = 5;

    private ListingRepository $listingRepository;

    public function __construct(ListingRepository $listingRepository)
    {
        $this->listingRepository = $listingRepository;
    }

    /**
     * Get market value estimate and sample listings.
     *
     * @return array{
     *   estimated_value: int|null,
     *   sample_listings: array,
     *   total_matches: int,
     *   message: string|null,
     *   is_extrapolated: bool,
     *   extrapolation_details: array|null
     * }
     */
    public function getMarketValue(int $year, string $make, string $model, ?int $mileage = null): array
    {
        $result = $this->listingRepository->findByYearMakeModel($year, $make, $model, $mileage);
        $listings = $result['listings'];
        $totalCount = $result['total_count'];
        $averagePrice = $result['average_price'];

        $estimatedValue = null;
        $message = null;
        $isExtrapolated = false;
        $extrapolationDetails = null;

        if ($totalCount >= self::MIN_LISTINGS && $averagePrice !== null) {
            $estimatedValue = (int) round($averagePrice / 100) * 100;
            $sampleListings = $this->formatSampleListings($listings);
        } elseif ($mileage !== null && $mileage > 0) {
            $extrapolation = $this->tryExtrapolate($year, $make, $model, $mileage);
            if ($extrapolation !== null) {
                $estimatedValue = $extrapolation['estimated_value'];
                $isExtrapolated = true;
                $extrapolationDetails = $extrapolation['details'];
                $sampleListings = $this->formatSampleListings($extrapolation['listings']);
                $totalCount = $extrapolation['total_count'];
                $message = sprintf(
                    'Extrapolated from %d listings across all mileages (linear regression).',
                    $extrapolation['total_count']
                );
            } else {
                $message = $totalCount === 0 ? 'No listings found.' : 'Insufficient data.';
                $sampleListings = $this->formatSampleListings($listings);
            }
        } else {
            $message = $totalCount === 0 ? 'No listings found.' : 'Insufficient data.';
            $sampleListings = $this->formatSampleListings($listings);
        }

        return [
            'estimated_value'       => $estimatedValue,
            'sample_listings'       => $sampleListings,
            'total_matches'         => $totalCount,
            'message'               => $message,
            'is_extrapolated'       => $isExtrapolated,
            'extrapolation_details' => $extrapolationDetails,
        ];
    }

    /**
     * Attempt linear extrapolation using all YMM listings with price+mileage data.
     * Returns null if insufficient data or regression is degenerate.
     */
    private function tryExtrapolate(int $year, string $make, string $model, int $mileage): ?array
    {
        $result = $this->listingRepository->findAllWithMileageData($year, $make, $model);
        if ($result['total_count'] < self::MIN_LISTINGS) {
            return null;
        }

        $points = [];
        foreach ($result['listings'] as $row) {
            $points[] = [
                'mileage' => (float) $row['listing_mileage'],
                'price'   => (float) $row['listing_price'],
            ];
        }

        $regression = $this->linearRegression($points);
        if ($regression === null) {
            return null;
        }

        $predicted = $regression['intercept'] + $regression['slope'] * $mileage;
        $predicted = max(0.0, $predicted);
        $estimatedValue = (int) round($predicted / 100) * 100;

        return [
            'estimated_value' => $estimatedValue,
            'total_count'     => $result['total_count'],
            'listings'        => $result['listings'],
            'details'         => [
                'method'        => 'linear_regression',
                'slope'         => round($regression['slope'], 6),
                'intercept'     => round($regression['intercept'], 2),
                'r_squared'     => round($regression['r_squared'], 4),
                'data_points'   => $result['total_count'],
                'mileage_range' => $regression['mileage_range'],
            ],
        ];
    }

    /**
     * Simple OLS linear regression: price = intercept + slope * mileage.
     *
     * @param array<int, array{mileage: float, price: float}> $points
     * @return array{slope: float, intercept: float, r_squared: float, mileage_range: array}|null
     */
    private function linearRegression(array $points): ?array
    {
        $n = count($points);
        if ($n < 2) {
            return null;
        }

        $sumX = 0.0;
        $sumY = 0.0;
        $sumXY = 0.0;
        $sumX2 = 0.0;
        $sumY2 = 0.0;
        $minMileage = PHP_FLOAT_MAX;
        $maxMileage = 0.0;

        foreach ($points as $p) {
            $x = $p['mileage'];
            $y = $p['price'];
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
            $sumY2 += $y * $y;
            $minMileage = min($minMileage, $x);
            $maxMileage = max($maxMileage, $x);
        }

        $denominator = $n * $sumX2 - $sumX * $sumX;
        if (abs($denominator) < 1e-10) {
            return null;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / $denominator;
        $intercept = ($sumY - $slope * $sumX) / $n;

        $meanY = $sumY / $n;
        $ssTot = $sumY2 - $n * $meanY * $meanY;
        $ssRes = 0.0;
        foreach ($points as $p) {
            $predicted = $intercept + $slope * $p['mileage'];
            $ssRes += ($p['price'] - $predicted) ** 2;
        }
        $rSquared = $ssTot > 0 ? 1.0 - ($ssRes / $ssTot) : 0.0;

        return [
            'slope'         => $slope,
            'intercept'     => $intercept,
            'r_squared'     => $rSquared,
            'mileage_range' => [
                'min' => (int) $minMileage,
                'max' => (int) $maxMileage,
            ],
        ];
    }

    /**
     * Format raw DB rows for API: vehicle description, price, mileage, location (design-doc §5.2 #6).
     *
     * @param array<int, array> $rows
     * @return array<int, array{vehicle: string, price: float|null, mileage: int|null, location: string}>
     */
    private function formatSampleListings(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $parts = array_filter([
                $row['year'] ?? null,
                $row['make'] ?? null,
                $row['model'] ?? null,
                $row['trim'] ?? null,
            ]);
            $vehicle = implode(' ', array_map('strval', $parts));
            $location = trim(implode(', ', array_filter([$row['dealer_city'] ?? '', $row['dealer_state'] ?? ''])));
            $out[] = [
                'vehicle'  => $vehicle,
                'price'    => isset($row['listing_price']) ? (float) $row['listing_price'] : null,
                'mileage'  => isset($row['listing_mileage']) ? (int) $row['listing_mileage'] : null,
                'location' => $location ?: null,
            ];
        }
        return $out;
    }
}
