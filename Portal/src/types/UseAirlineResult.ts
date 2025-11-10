import type { Airline } from "./CoreSwaggerTypes.ts";

export interface UseAirlineResult {
    airline?: Airline
    updateAirlineName: (name: string) => Promise<void>
    updateAirlineLogo: (logo: string) => Promise<void>
    removeAirline: () => Promise<void>
}