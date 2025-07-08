DELETE FROM configuration WHERE type = 'CHAT_REQUESTS';
INSERT INTO configuration (type, levels, `key`, value) VALUES ('DEFAULT_LANGUAGE', 'private', NULL, 'Czech');