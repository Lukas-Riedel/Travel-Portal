import type { Airport } from "./CoreSwaggerTypes.ts"

export interface UseAirportResult {
    airport: Airport | null
    updateAirportLongName: (name: string) => Promise<Airport>
    updateAirportCountry: (country: string) => Promise<Airport>
}