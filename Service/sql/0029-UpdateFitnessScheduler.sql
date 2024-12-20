UPDATE scheduler
SET interval_query = 'SELECT value FROM configuration WHERE type = ''FITNESS_RECORD_DURATION'''
WHERE name = 'UPDATE_FITNESS_DATA'