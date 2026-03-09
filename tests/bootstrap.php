<?php

declare(strict_types=1);

$baseDir = dirname(__DIR__);
require $baseDir . '/vendor/autoload.php';

$config = [
    'host'     => getenv('MYSQL_HOST') ?: 'localhost',
    'port'     => (int) (getenv('MYSQL_PORT') ?: '3306'),
    'user'     => getenv('MYSQL_USER') ?: 'root',
    'password' => getenv('MYSQL_PASSWORD') ?: '',
    'database' => getenv('MYSQL_DATABASE') ?: 'moaz_carvalue_test',
];

$dsnNoDb = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $config['host'], $config['port']);
$pdo = new PDO($dsnNoDb, $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE DATABASE IF NOT EXISTS `moaz_carvalue_test` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec('USE `moaz_carvalue_test`');

$createTable = <<<'SQL'
CREATE TABLE IF NOT EXISTS `listings` (
  `id`                        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `vin`                       VARCHAR(17)     DEFAULT NULL,
  `year`                      SMALLINT        NOT NULL,
  `make`                      VARCHAR(64)     NOT NULL,
  `model`                     VARCHAR(128)    NOT NULL,
  `trim`                      VARCHAR(128)    DEFAULT NULL,
  `dealer_name`               VARCHAR(255)    DEFAULT NULL,
  `dealer_street`             VARCHAR(255)    DEFAULT NULL,
  `dealer_city`               VARCHAR(128)    DEFAULT NULL,
  `dealer_state`              VARCHAR(16)     DEFAULT NULL,
  `dealer_zip`                VARCHAR(16)     DEFAULT NULL,
  `listing_price`             DECIMAL(12,2)   DEFAULT NULL,
  `listing_mileage`           INT UNSIGNED    DEFAULT NULL,
  `used`                      TINYINT(1)      DEFAULT NULL,
  `certified`                 TINYINT(1)      DEFAULT NULL,
  `style`                     VARCHAR(128)    DEFAULT NULL,
  `driven_wheels`             VARCHAR(64)     DEFAULT NULL,
  `engine`                    VARCHAR(128)    DEFAULT NULL,
  `fuel_type`                 VARCHAR(64)     DEFAULT NULL,
  `exterior_color`            VARCHAR(64)     DEFAULT NULL,
  `interior_color`            VARCHAR(64)     DEFAULT NULL,
  `seller_website`            VARCHAR(512)    DEFAULT NULL,
  `first_seen_date`           DATE            DEFAULT NULL,
  `last_seen_date`            DATE            DEFAULT NULL,
  `dealer_vdp_last_seen_date` DATE            DEFAULT NULL,
  `listing_status`            VARCHAR(64)     DEFAULT NULL,
  `created_at`                TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_year_make_model` (`year`, `make`, `model`),
  KEY `idx_listing_mileage` (`listing_mileage`),
  KEY `idx_listing_price` (`listing_price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
$pdo->exec($createTable);

$pdo->exec('TRUNCATE TABLE listings');

$pdo->exec("INSERT INTO listings (year, make, model, trim, dealer_city, dealer_state, listing_price, listing_mileage) VALUES
(2015, 'Toyota', 'Camry', 'CE', 'Seattle', 'WA', 10000, 50000),
(2015, 'Toyota', 'Camry', 'CE', 'Dallas', 'TX', 11000, 60000),
(2015, 'Toyota', 'Camry', 'LE', 'Newark', 'NJ', 12000, 70000),
(2015, 'Toyota', 'Camry', 'LE', 'Phoenix', 'AZ', 13000, 80000),
(2015, 'Toyota', 'Camry', 'XLE', 'Austin', 'TX', 14000, 90000)");

$pdo->exec("INSERT INTO listings (year, make, model, trim, dealer_city, dealer_state, listing_price, listing_mileage) VALUES
(2015, 'Toyota', 'Camry', NULL, 'Boston', 'MA', NULL, 85000),
(2015, 'Toyota', 'Camry', NULL, 'Denver', 'CO', NULL, 95000),
(2015, 'Toyota', 'Camry', NULL, 'Miami', 'FL', NULL, 105000)");

$stmt = $pdo->prepare('INSERT INTO listings (year, make, model, trim, dealer_city, dealer_state, listing_price, listing_mileage) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
for ($i = 0; $i < 101; $i++) {
    $stmt->execute([2017, 'Honda', 'Civic', 'LX', "City$i", 'ST', 15000 + ($i % 1000), 30000 + $i * 100]);
}
