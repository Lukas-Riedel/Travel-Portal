import type { Airline } from "./CoreSwaggerTypes.ts";

export interface UseAirlinesResult {
    airlines?: Airline[]
    createAirline: (name: string) => Promise<void>
    createAirlineCode: (airlineId: string, code: string) => Promise<void>
    updateAirlineName: (airlineId: string, name: string) => Promise<void>
    updateAirlineLogo: (airlineId: string, logo: string) => Promise<void>
    removeAirline: (airlineId: string) => Promise<void>
    removeAirlineCode: (airlineId: string, code: string) => Promise<void>
}