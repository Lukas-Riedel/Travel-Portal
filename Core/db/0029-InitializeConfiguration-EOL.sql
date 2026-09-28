insert into configuration (key, private, value) values
    ('dynamicLabels', false, '[{"name": "Last year", "interval": 31536000}, {"name": "Last month", "interval": 2592000}]')
    on conflict (key) do nothing
EOL

insert into configuration (key, private, value) values
    ('homeLocation', false, '{"country": "Czechia", "latitude": 50.0755, "longitude": 14.4378, "timezone": "Europe/Prague", "countryCode": "CZ"}')
    on conflict (key) do nothing
EOL

insert into configuration (key, private, value) values
    ('expensify', false, '{"mainCurrency": "EUR"}')
    on conflict (key) do nothing
EOL

insert into configuration (key, private, value) values
    ('calendar', true, '{"stays": "https://calendar.google.com/calendar/ical/example%40group.calendar.google.com/private-token/basic.ics", "trips": "https://calendar.google.com/calendar/ical/example%40group.calendar.google.com/private-token/basic.ics", "places": "https://calendar.google.com/calendar/ical/example%40group.calendar.google.com/private-token/basic.ics", "flights": "https://calendar.google.com/calendar/ical/example%40group.calendar.google.com/private-token/basic.ics", "watchedFlights": "https://calendar.google.com/calendar/ical/example%40group.calendar.google.com/private-token/basic.ics"}')
    on conflict (key) do nothing
EOL

insert into configuration (key, private, value) values
    ('timeTracking', false, '{"currentFte": 1.0, "expectedOvertimePerDay": 0.0, "openingBalance": {"tenure": 0, "selfcare": 16, "vacation": 200}}')
    on conflict (key) do nothing
EOL

insert into configuration (key, private, value) values
    ('generativeContentPrompt', true, '{"placeExcerpt": "Write a travel blog article about {name} ({country}) in region {region}.", "alternativeAddress": "Find an alternative name for the place \"{address}\" for use with the Geocoding API.", "tripHighlightsSelecting": "CLIP prompt for a trip covering: {places}.", "yearHighlightsSelecting": "CLIP prompt for the best moments of the year from: {places}.", "placeHighlightsSelecting": "CLIP prompt for {name} in {country}.", "categoryHighlightsSelecting": "CLIP prompt for the region {name} containing: {places}."}')
    on conflict (key) do nothing
EOL

insert into configuration (key, private, value) values
    ('openLineage', true, '{"producer": {"ibmCloud": {"enabled": false}, "googleDrive": {"enabled": false}}}')
    on conflict (key) do nothing
EOL

insert into configuration (key, private, value) values
    ('flightReminders', false, '[{"title": "Upcoming flight", "text": "Flight {flight} departs at {formattedTime} local time.", "secondsBefore": 14400}]')
    on conflict (key) do nothing
EOL

insert into configuration (key, private, value) values
    ('agent', false, '{"agent.core.workers": 16, "agent.core.queue.name": "AgentQueue", "agent.core.data.directory": "${user.home}/.agent", "agent.core.registration.interval": 30, "agent.photo.compression.rate": 0.4, "agent.photo.uploading.asynchronous": true, "agent.photo.synchronization.interval": 15, "agent.backup.synchronization.interval": 15, "agent.backup.file.extensions": ["arw"], "spring.datasource.url": "jdbc:h2:file:${agent.core.data.directory}/db;DB_CLOSE_ON_EXIT=FALSE", "spring.datasource.username": "sa", "spring.datasource.password": "", "spring.datasource.driver-class-name": "org.h2.Driver", "spring.jpa.database-platform": "org.hibernate.dialect.H2Dialect", "spring.jpa.hibernate.ddl-auto": "update", "spring.rabbitmq.ssl.enabled": true, "spring.rabbitmq.listener.simple.prefetch": 1, "spring.rabbitmq.listener.simple.concurrency": 5, "spring.rabbitmq.listener.simple.max-concurrency": 10}')
    on conflict (key) do nothing
EOL

insert into configuration (key, private, value) values
    ('highlight', true, '{"attribute": {"sky": [{"id": "terrible", "text": "Overcast day with depressive grey weather and dense fog.", "value": 10}, {"id": "bad", "text": "Photo taken in gloomy weather under heavy dark storm clouds.", "value": 30}, {"id": "average", "text": "White fluffy clouds covering the sun in the sky.", "value": 50}, {"id": "good", "text": "Photo with a clear bright blue sky.", "value": 95}, {"id": "great", "text": "Photo with a clear bright blue sky and dramatic photogenic clouds.", "value": 100}], "shadows": [{"id": "terrible", "text": "Dark underexposed photo with silhouettes in the dark.", "value": 35}, {"id": "bad", "text": "Flat photo with low contrast, dull light and dreary weather.", "value": 40}, {"id": "good", "text": "Bright daytime photo with gentle natural light.", "value": 60}, {"id": "great", "text": "High-contrast photo with sharp direct sunlight and deep dark shadows.", "value": 100}], "atmosphere": [{"id": "terrible", "text": "Photo with heavy smog, dense haze and very poor visibility.", "value": 30}, {"id": "bad", "text": "Photo with mild atmospheric haze and reduced colour sharpness.", "value": 80}, {"id": "good", "text": "Photo with clean air, good visibility and natural colours.", "value": 95}, {"id": "great", "text": "Photo with crystal clear air and exceptional sharpness into the distance.", "value": 100}], "impression": [{"id": "bad", "value": 5}, {"id": "average", "value": 30}, {"id": "good", "value": 100}], "composition": [{"id": "bad", "text": "Photo with chaotic composition, disorganised elements and poor cropping.", "value": 5}, {"id": "average", "text": "Photo with a simple, ordinary and average composition.", "value": 30}, {"id": "good", "text": "Photo with professional composition, balanced framing and a clear focal point.", "value": 100}], "circumstances": [{"id": "terrible", "text": "Scaffolding, crowds of people or litter.", "value": 20}, {"id": "bad", "text": "Photo with a distracting background and elements drawing attention away.", "value": 70}, {"id": "good", "text": "Clean photo with minimal distracting elements in the background.", "value": 90}, {"id": "great", "text": "Aesthetically striking and authentic scene with clear intent and no visual noise.", "value": 100}]}, "negativeTerms": ["macro photography", "close-up shot", "stairs", "interiors", "overcast sky", "museum exhibits", "furniture", "blurred background", "gesturing people", "flags", "insects", "textured surfaces", "photos in the dark", "running children"]}')
    on conflict (key) do nothing
EOL
