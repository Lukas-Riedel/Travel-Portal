ALTER TABLE category_identifier
MODIFY COLUMN category TEXT NOT NULL;

UPDATE category_identifier SET category = 'continent' WHERE category = 'CONTINENT';
UPDATE category_identifier SET category = 'country' WHERE category = 'COUNTRY';
UPDATE category_identifier SET category = 'administrative' WHERE category = 'ADMINISTRATIVE';
UPDATE category_identifier SET category = 'ocean' WHERE category = 'OCEAN';
UPDATE category_identifier SET category = 'sea' WHERE category = 'SEA';
UPDATE category_identifier SET category = 'bay' WHERE category = 'BAY';
UPDATE category_identifier SET category = 'island' WHERE category = 'ISLAND';
UPDATE category_identifier SET category = 'region' WHERE category = 'REGION';

DELETE FROM device;
ALTER TABLE device
MODIFY COLUMN type TEXT NOT NULL;

ALTER TABLE expense
MODIFY COLUMN type TEXT NOT NULL;

UPDATE expense SET type = 'flight' WHERE type = 'FLIGHT';
UPDATE expense SET type = 'hotel' WHERE type = 'HOTEL';
UPDATE expense SET type = 'attraction' WHERE type = 'ATTRACTION';
UPDATE expense SET type = 'intercityTransport' WHERE type = 'INTERCITY_TRANSPORT';
UPDATE expense SET type = 'publicTransport' WHERE type = 'PUBLIC_TRANSPORT';
UPDATE expense SET type = 'organizedTour' WHERE type = 'ORGANIZED_TOUR';
UPDATE expense SET type = 'carRental' WHERE type = 'CAR_RENTAL';
UPDATE expense SET type = 'fuel' WHERE type = 'FUEL';
UPDATE expense SET type = 'cityTax' WHERE type = 'CITY_TAX';
UPDATE expense SET type = 'parking' WHERE type = 'PARKING';
UPDATE expense SET type = 'airportTransfer' WHERE type = 'AIRPORT_TRANSFER';
UPDATE expense SET type = 'visa' WHERE type = 'VISA';
UPDATE expense SET type = 'other' WHERE type = 'OTHER';

ALTER TABLE region_composite
ADD COLUMN included TINYINT(4) NOT NULL;

UPDATE region_composite
SET included = CASE type
    WHEN 'INCLUDE' THEN 1
    WHEN 'EXCLUDE' THEN 0
END;

ALTER TABLE region_composite
  DROP COLUMN type;