import type { Address } from "../types/CoreSwaggerTypes.ts"

export interface BridgeXDeviceData {
    battery?: number
    address?: Address
    latitude?: number
    longitude?: number
    timezone?: string
}