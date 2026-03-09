<?php

declare(strict_types=1);

namespace App;

/**
 * Computes market value estimate from listings (average rounded to nearest hundred).
 * Requires at least MIN_LISTINGS for an estimate; otherwise returns "Insufficient data" (design-doc §3.2).
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
     *   message: string|null
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

        if ($totalCount >= self::MIN_LISTINGS && $averagePrice !== null) {
            $estimatedValue = (int) round($averagePrice / 100) * 100;
            $sampleListings = $this->formatSampleListings($listings);
        } else {
            $message = $totalCount === 0 ? 'No listings found.' : 'Insufficient data.';
            $sampleListings = $this->formatSampleListings($listings);
        }

        return [
            'estimated_value'  => $estimatedValue,
            'sample_listings'  => $sampleListings,
            'total_matches'    => $totalCount,
            'message'          => $message,
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
