-- CarValue: load pipe-separated (PSV) data into listings (design-doc §2.2)
-- File path is substituted by import script: @DATA_FILE@
-- Uses MariaDB/MySQL LOAD DATA LOCAL INFILE with FIELDS TERMINATED BY '|'

USE `moaz-carvalue`;

LOAD DATA LOCAL INFILE '@DATA_FILE@'
INTO TABLE `listings`
CHARACTER SET utf8mb4
FIELDS TERMINATED BY '|'
LINES TERMINATED BY '\n'
IGNORE 1 LINES
(
  @vin, @year, @make, @model, @trim,
  @dealer_name, @dealer_street, @dealer_city, @dealer_state, @dealer_zip,
  @listing_price, @listing_mileage, @used, @certified,
  @style, @driven_wheels, @engine, @fuel_type,
  @exterior_color, @interior_color, @seller_website,
  @first_seen_date, @last_seen_date, @dealer_vdp_last_seen_date, @listing_status
)
SET
  vin = NULLIF(TRIM(@vin), ''),
  year = CAST(NULLIF(TRIM(@year), '') AS SIGNED),
  make = NULLIF(TRIM(@make), ''),
  model = NULLIF(TRIM(@model), ''),
  trim = NULLIF(TRIM(@trim), ''),
  dealer_name = NULLIF(TRIM(@dealer_name), ''),
  dealer_street = NULLIF(TRIM(@dealer_street), ''),
  dealer_city = NULLIF(TRIM(@dealer_city), ''),
  dealer_state = NULLIF(TRIM(@dealer_state), ''),
  dealer_zip = NULLIF(TRIM(@dealer_zip), ''),
  listing_price = NULLIF(TRIM(@listing_price), ''),
  listing_mileage = CASE WHEN NULLIF(TRIM(@listing_mileage), '') IS NULL THEN NULL ELSE CAST(NULLIF(TRIM(@listing_mileage), '') AS UNSIGNED) END,
  used = CASE WHEN TRIM(@used) = 'TRUE' THEN 1 WHEN TRIM(@used) = 'FALSE' THEN 0 ELSE NULL END,
  certified = CASE WHEN TRIM(@certified) = 'TRUE' THEN 1 WHEN TRIM(@certified) = 'FALSE' THEN 0 ELSE NULL END,
  style = NULLIF(TRIM(@style), ''),
  driven_wheels = NULLIF(TRIM(@driven_wheels), ''),
  engine = NULLIF(TRIM(@engine), ''),
  fuel_type = NULLIF(TRIM(@fuel_type), ''),
  exterior_color = NULLIF(TRIM(@exterior_color), ''),
  interior_color = NULLIF(TRIM(@interior_color), ''),
  seller_website = NULLIF(TRIM(@seller_website), ''),
  first_seen_date = NULLIF(TRIM(@first_seen_date), ''),
  last_seen_date = NULLIF(TRIM(@last_seen_date), ''),
  dealer_vdp_last_seen_date = NULLIF(TRIM(@dealer_vdp_last_seen_date), ''),
  listing_status = NULLIF(TRIM(@listing_status), ''),
  created_at = CURRENT_TIMESTAMP
;
