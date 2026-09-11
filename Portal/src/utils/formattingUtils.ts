import { type ClassValue,clsx } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]): string {
    return twMerge(clsx(inputs))
}

// TODO: Move to some interface common for all entities.
export function getEntityPrettyName(name: string | number): string {
    return String(name).replace(/\s*\(.*/, "").trim()
}

export function formatDeviceType(type: string): string {
    return type.toLowerCase().replace(/^./, c => c.toUpperCase())
}