import type { Airport } from "./CoreSwaggerTypes.ts"

export interface UseAirportsResult {
    airports?: Airport[]
    updateAirportLongName: (airportId: string, longName: string) => Promise<void>
}