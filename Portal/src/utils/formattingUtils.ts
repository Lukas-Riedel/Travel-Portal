import { clsx, type ClassValue } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs))
}

// TODO: Move to some interface common for all entities.
export function getEntityPrettyName(name: string | number) {
    return String(name).replace(/\s*\(.*/, "").trim()
}

export function formatDeviceType(type: string): string {
    return type.toLowerCase().replace(/^./, c => c.toUpperCase())
}

export function getMinMaxRange(items: number[], formatValue?: (value: number) => string): string {
    const min = Math.min(...items)
    const max = Math.max(...items)

    if (formatValue) {
        return min === max ? formatValue(min) : `${min}-${formatValue(max)}`
    }

    return min === max ? `${min}` : `${min}-${max}`
}