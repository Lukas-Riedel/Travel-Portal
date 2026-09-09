import type { Airline } from "./CoreSwaggerTypes.ts"

export interface UseAirlineResult {
    airline: Airline | null
    updateAirlineName: (name: string) => Promise<Airline>
    updateAirlineLogo: (logo: string) => Promise<Airline>
    removeAirline: () => Promise<void>
}