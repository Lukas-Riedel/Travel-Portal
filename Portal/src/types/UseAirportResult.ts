import type { Airport } from "./CoreSwaggerTypes.ts";

export interface UseAirportResult {
    airport?: Airport
    updateAirportLongName: (name: string) => Promise<Airport>
    updateAirportCountry: (country: string) => Promise<Airport>
}