-- Integration test fixtures
-- 2015 Toyota Camry: 5 with price (avg 12000), 3 with null price (excluded)
-- 2017 Honda Civic: 101 rows inserted via bootstrap PHP (sample cap test)
-- No 1990 Foo Bar (no matches)

USE `moaz_carvalue_test`;

INSERT INTO listings (year, make, model, trim, dealer_city, dealer_state, listing_price, listing_mileage) VALUES
(2015, 'Toyota', 'Camry', 'CE', 'Seattle', 'WA', 10000, 50000),
(2015, 'Toyota', 'Camry', 'CE', 'Dallas', 'TX', 11000, 60000),
(2015, 'Toyota', 'Camry', 'LE', 'Newark', 'NJ', 12000, 70000),
(2015, 'Toyota', 'Camry', 'LE', 'Phoenix', 'AZ', 13000, 80000),
(2015, 'Toyota', 'Camry', 'XLE', 'Austin', 'TX', 14000, 90000);

INSERT INTO listings (year, make, model, trim, dealer_city, dealer_state, listing_price, listing_mileage) VALUES
(2015, 'Toyota', 'Camry', NULL, 'Boston', 'MA', NULL, 85000),
(2015, 'Toyota', 'Camry', NULL, 'Denver', 'CO', NULL, 95000),
(2015, 'Toyota', 'Camry', NULL, 'Miami', 'FL', NULL, 105000);
