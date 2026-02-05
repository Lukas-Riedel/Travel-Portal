import type { Airline } from "./CoreSwaggerTypes.ts";

export interface UseAirlinesResult {
    airlines?: Airline[]
    createAirline: (name: string) => Promise<Airline>
    createAirlineCode: (airlineId: string, code: string) => Promise<Airline>
    updateAirlineName: (airlineId: string, name: string) => Promise<Airline>
    updateAirlineLogo: (airlineId: string, logo: string) => Promise<Airline>
    removeAirline: (airlineId: string) => Promise<void>
    removeAirlineCode: (airlineId: string, code: string) => Promise<void>
}