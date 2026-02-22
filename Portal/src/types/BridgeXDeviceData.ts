import type { KnownAddress } from "./KnownAddress.ts"

export interface BridgeXDeviceData {
    battery?: number
    address?: KnownAddress
    latitude?: number
    longitude?: number
    timezone?: string
}