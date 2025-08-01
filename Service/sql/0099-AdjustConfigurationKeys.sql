DELETE FROM configuration WHERE `key` = 'contactEmail';
INSERT INTO configuration (`key`, private, value) VALUES ('contactDetails', 1, '{"email":"lukas.riedel24@gmail.com"}');

DELETE FROM configuration WHERE `key` = 'contactEmail';
INSERT INTO configuration (`key`, private, value) VALUES ('trips', 0, '{"dayTripsName":"Výlety"}');

DELETE FROM configuration WHERE `key` = 'expectedOvertimeHoursPerDay';
DELETE FROM configuration WHERE `key` = 'currentFte';
DELETE FROM configuration WHERE `key` = 'timeOffHours';
INSERT INTO configuration (`key`, private, value) VALUES ('timeTracking', 0, '{"expectedOvertimeHoursPerDay":1.6,"currentFte":0.8,"timeOffHours":{"vacation":160,"tenure":0,"selfcare":12.8}}');

DELETE FROM configuration WHERE `key` = 'chatResponsesLanguage';
INSERT INTO configuration (`key`, private, value) VALUES ('generativeChat', 1, '{"language":"Czech"}');