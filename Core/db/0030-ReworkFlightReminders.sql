alter table task drop column last_notification;

update configuration
set key = 'ephemeralTasks', value = '[{"source":"flight.scheduled","title":"Upcoming flight","text":"Flight {flight} departs at {formattedTime} local time.","priority":"medium","trigger":{"seconds":3600},"notificationInterval":null},{"source":"flight.watched","title":"Unwatched flight","text":"Flight {flight} departs in less than 3 months ({formattedTime} local time). Don''t forget to purchase it.","priority":"low","trigger":{"seconds":7776000},"notificationInterval":259200}]'
where key = 'flightReminders';
