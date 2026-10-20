# Geocoding Read Skill

## Purpose & Activation
Provides capabilities to look up the timezone for an address or location using geocoding.
Activate and apply this skill when the user asks which timezone applies to a location or needs a location's timezone for a date or time operation.

## Geocoding Read Guidelines & Actions
1. **Obtaining Location Timezone**
   - When the user asks for the timezone of an address or location, call `get_location_timezone` with the address or location name.
   - Use the returned IANA timezone identifier. **NEVER invent a location's timezone** from its name, country, or UTC offset.