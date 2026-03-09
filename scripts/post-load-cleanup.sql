-- CarValue: remove invalid rows (missing year, make, or model) after load (design-doc §2.2)
USE `moaz-carvalue`;

DELETE FROM `listings`
WHERE year = 0 OR make = '' OR model = '';
