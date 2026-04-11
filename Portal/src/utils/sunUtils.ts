import SunCalc from "suncalc"
import type { Coordinates } from "../types/Coordinates.ts"
import { toDegrees } from "./geocodingUtils.ts"
import { getDate } from "./timeUtils.ts"

export function getSunAltitude(dateOrTimestamp: number | Date, coordinates: Coordinates): number {
    return toDegrees(SunCalc.getPosition(getDate(dateOrTimestamp), coordinates.latitude, coordinates.longitude).altitude)
}

export function getSunAzimuth(dateOrTimestamp: number | Date, coordinates: Coordinates): number {
    return toDegrees(SunCalc.getPosition(getDate(dateOrTimestamp), coordinates.latitude, coordinates.longitude).azimuth)
}