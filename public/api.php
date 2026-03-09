<?php

declare(strict_types=1);

/**
 * CarValue JSON API (design-doc §5.1).
 * GET params: year (required), make (required), model (required), mileage (optional).
 */

$baseDir = dirname(__DIR__);
require $baseDir . '/vendor/autoload.php';

$config = require $baseDir . '/config/database.php';
$pdo = \App\Database::createFromConfig($config);
$repo = new \App\ListingRepository($pdo);
$service = new \App\MarketValueService($repo);

if (PHP_SAPI !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
}

$year   = trim((string) ($_GET['year'] ?? ''));
$make   = $_GET['make']   ?? '';
$model  = $_GET['model']  ?? '';
$mileage = isset($_GET['mileage']) ? trim((string) $_GET['mileage']) : null;

// Validation: required params (design-doc §5.1 — 400 if missing or invalid)
$errors = [];

if ($year === '') {
    $errors[] = 'year is required';
} elseif (!ctype_digit($year)) {
    $errors[] = 'year must be a number';
} else {
    $yearInt = (int) $year;
    if ($yearInt < 1900 || $yearInt > 2100) {
        $errors[] = 'year must be between 1900 and 2100';
    }
}

if (trim((string) $make) === '') {
    $errors[] = 'make is required';
}
if (trim((string) $model) === '') {
    $errors[] = 'model is required';
}

if ($mileage !== null && $mileage !== '') {
    if (!ctype_digit($mileage)) {
        $errors[] = 'mileage must be a non-negative integer';
    } else {
        $mileageInt = (int) $mileage;
        if ($mileageInt < 0) {
            $errors[] = 'mileage must be non-negative';
        }
    }
}

if ($errors !== []) {
    http_response_code(400);
    echo json_encode(['error' => 'Validation failed.', 'details' => $errors]);
    return;
}

$yearInt = (int) $year;
$make = trim((string) $make);
$model = trim((string) $model);
$mileageInt = null;
if ($mileage !== null && $mileage !== '') {
    $mileageInt = (int) $mileage;
}

$result = $service->getMarketValue($yearInt, $make, $model, $mileageInt);

http_response_code(200);
echo json_encode($result, JSON_PRETTY_PRINT);
