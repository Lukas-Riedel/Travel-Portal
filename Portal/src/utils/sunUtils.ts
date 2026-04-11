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

export function getSunrise(dateOrTimestamp: number | Date, coordinates: Coordinates): number | null {
    const sunrise = SunCalc.getTimes(getDate(dateOrTimestamp), coordinates.latitude, coordinates.longitude).sunrise
    return isNaN(sunrise) ? null : (sunrise.getTime() / 1000)
}

export function getSunset(dateOrTimestamp: number | Date, coordinates: Coordinates): number | null {
    const sunset = SunCalc.getTimes(getDate(dateOrTimestamp), coordinates.latitude, coordinates.longitude).sunset
    return isNaN(sunset) ? null : (sunset.getTime() / 1000)
}