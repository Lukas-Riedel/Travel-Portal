import type { Airport } from "./CoreSwaggerTypes.ts"

export interface FlightPath {
    from: Airport
    to: Airport
    count: number
}