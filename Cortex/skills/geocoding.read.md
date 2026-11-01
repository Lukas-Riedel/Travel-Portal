# Geocoding Read Skill

1. **Obtaining Location Timezone**
   - When the user asks for the timezone of an address or location, call `get_location_timezone` with the address or location name.
   - Use the returned IANA timezone identifier. **NEVER invent a location's timezone** from its name, country, or UTC offset.