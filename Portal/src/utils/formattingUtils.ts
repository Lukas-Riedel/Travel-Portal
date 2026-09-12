import { type ClassValue, clsx } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]): string {
    return twMerge(clsx(inputs))
}

// TODO: Design an interface common for all entities that can be pretty-printed (similar to Indexable or Navigable), and use it here.
export function getEntityPrettyName(name: string | number): string {
    return String(name).replace(/\s*\(.*/, "").trim()
}

export function formatDeviceType(type: string): string {
    return type.toLowerCase().replace(/^./, c => c.toUpperCase())
}

export function getUnicode(unicode: string): string {
    return String.fromCodePoint(...unicode.split("-").map(cp => parseInt(cp, 16)))
}