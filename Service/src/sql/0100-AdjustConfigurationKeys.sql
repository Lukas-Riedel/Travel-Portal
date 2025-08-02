DELETE FROM configuration WHERE `key` = 'specialTripNames';

DELETE FROM configuration WHERE `key` = 'mainCurrency';
INSERT INTO configuration (`key`, private, value) VALUES ('expensify', 0, '{"mainCurrency":"CZK"}');