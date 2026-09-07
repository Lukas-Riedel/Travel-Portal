import { type ClassValue,clsx } from "clsx"
import { twMerge } from "tailwind-merge"

import type { Trip } from "../classes/Trip"
import type { TripIdentifier } from "../types/CoreSwaggerTypes"
import { isTripCandidate } from "./tripUtils"

export function cn(...inputs: ClassValue[]): string {
    return twMerge(clsx(inputs))
}

// TODO: Move to some interface common for all entities.
export function getEntityPrettyName(name: string | number): string {
    return String(name).replace(/\s*\(.*/, "").trim()
}

// TODO: Eventually move to getEntityPrettyName (when moving to some interface common for all entities).
export function getTripFullName(trip: Trip | TripIdentifier): string {
    return isTripCandidate(trip) ? trip.name : `${trip.name} ${trip.year}`
}

export function formatDeviceType(type: string): string {
    return type.toLowerCase().replace(/^./, c => c.toUpperCase())
}