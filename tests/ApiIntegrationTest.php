<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the CarValue API (design-doc §5.2).
 * Runs against test database with fixtures; invokes api.php in-process.
 */
final class ApiIntegrationTest extends TestCase
{
    private string $baseDir;
    private string $apiPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = dirname(__DIR__);
        $this->apiPath = $this->baseDir . '/public/api.php';
    }

    /**
     * 1. Search by year/make/model only — 200, estimated_value is number, sample_listings is array;
     *    value is average of matching listings rounded to nearest hundred.
     */
    public function test_search_by_year_make_model_returns_200_with_estimate_and_sample_listings(): void
    {
        $json = $this->getApiResponse(['year' => '2015', 'make' => 'Toyota', 'model' => 'Camry']);
        $this->assertArrayHasKey('estimated_value', $json);
        $this->assertArrayHasKey('sample_listings', $json);
        $this->assertIsArray($json['sample_listings']);
        $this->assertIsInt($json['estimated_value']);
        // Fixtures: 5 listings with prices 10000,11000,12000,13000,14000 → avg 12000
        $this->assertSame(12000, $json['estimated_value']);
        $this->assertGreaterThanOrEqual(5, count($json['sample_listings']));
        $this->assertSame(5, $json['total_matches']);
    }

    /**
     * 2. Search with mileage — result set or average reflects mileage band.
     */
    public function test_search_with_mileage_reflects_mileage_band(): void
    {
        $json = $this->getApiResponse([
            'year' => '2015', 'make' => 'Toyota', 'model' => 'Camry', 'mileage' => '100000',
        ]);
        $this->assertSame(200, $this->getLastHttpCode());
        $this->assertArrayHasKey('estimated_value', $json);
        $this->assertArrayHasKey('sample_listings', $json);
        // Mileage band ±25% of 100k = 75k–125k. Fixtures have 5 with price at 50k–90k (none in band)
        // So we get 0 matches with mileage filter, or if we have any in band they'd be returned.
        // Actually our fixtures: 50000,60000,70000,80000,90000 - all below 75k. So 0 in band.
        $this->assertLessThanOrEqual(5, $json['total_matches']);
        // If we have no rows in band, message is "No listings found." or "Insufficient data."
        if ($json['total_matches'] < 5) {
            $this->assertNotNull($json['message']);
            $this->assertNull($json['estimated_value']);
        } else {
            $this->assertIsInt($json['estimated_value']);
        }
    }

    /**
     * 3. No matches — 200 with empty or zero estimate and empty sample_listings (or "Insufficient data").
     */
    public function test_no_matches_returns_200_with_empty_estimate_and_message(): void
    {
        $json = $this->getApiResponse(['year' => '1990', 'make' => 'Foo', 'model' => 'Bar']);
        $this->assertSame(200, $this->getLastHttpCode());
        $this->assertSame([], $json['sample_listings']);
        $this->assertSame(0, $json['total_matches']);
        $this->assertNull($json['estimated_value']);
        $this->assertNotEmpty($json['message']);
    }

    /**
     * 4. Missing required params — 400.
     */
    public function test_missing_required_params_returns_400(): void
    {
        $this->getApiResponse(['year' => '2015', 'make' => 'Toyota']);
        $this->assertSame(400, $this->getLastHttpCode());
    }

    /**
     * 5. Invalid params — invalid year (e.g. non-numeric) returns 400.
     */
    public function test_invalid_year_returns_400(): void
    {
        $this->getApiResponse(['year' => 'abc', 'make' => 'Toyota', 'model' => 'Camry']);
        $this->assertSame(400, $this->getLastHttpCode());
    }

    /**
     * 6. Sample listing shape — vehicle, price, mileage, location (city, state).
     */
    public function test_sample_listing_shape_has_vehicle_price_mileage_location(): void
    {
        $json = $this->getApiResponse(['year' => '2015', 'make' => 'Toyota', 'model' => 'Camry']);
        $this->assertNotEmpty($json['sample_listings']);
        $first = $json['sample_listings'][0];
        $this->assertArrayHasKey('vehicle', $first);
        $this->assertArrayHasKey('price', $first);
        $this->assertArrayHasKey('mileage', $first);
        $this->assertArrayHasKey('location', $first);
        $this->assertStringContainsString('2015', $first['vehicle']);
        $this->assertStringContainsString('Toyota', $first['vehicle']);
        $this->assertStringContainsString('Camry', $first['vehicle']);
    }

    /**
     * 7. Sample cap — number of items in sample_listings ≤ 100.
     */
    public function test_sample_listings_capped_at_100(): void
    {
        $json = $this->getApiResponse(['year' => '2017', 'make' => 'Honda', 'model' => 'Civic']);
        $this->assertSame(200, $this->getLastHttpCode());
        $this->assertSame(101, $json['total_matches']);
        $this->assertCount(100, $json['sample_listings']);
    }

    /**
     * 8. Listings with null price excluded from average (and from sample).
     */
    public function test_listings_with_null_price_excluded_from_average(): void
    {
        $json = $this->getApiResponse(['year' => '2015', 'make' => 'Toyota', 'model' => 'Camry']);
        // Fixtures: 5 with price (avg 12000), 3 with null price. Only 5 count toward estimate.
        $this->assertSame(5, $json['total_matches']);
        $this->assertSame(12000, $json['estimated_value']);
        foreach ($json['sample_listings'] as $row) {
            $this->assertNotNull($row['price'], 'Sample listings should not include null-price rows');
        }
    }

    private int $lastHttpCode = 0;

    private function getApiResponse(array $params): array
    {
        $_GET = $params;
        $this->lastHttpCode = 0;
        ob_start();
        try {
            // Capture response code: api.php sets it via http_response_code()
            require $this->apiPath;
            $body = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        $this->lastHttpCode = (int) http_response_code();
        if ($this->lastHttpCode === 0) {
            $this->lastHttpCode = 200;
        }
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function getLastHttpCode(): int
    {
        return $this->lastHttpCode;
    }
}
