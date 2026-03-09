-- CarValue: create database and listings table (design-doc §4.1)
-- Run with: mysql [options] < scripts/schema.sql

CREATE DATABASE IF NOT EXISTS `moaz-carvalue`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `moaz-carvalue`;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
