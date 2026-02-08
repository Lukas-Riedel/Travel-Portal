import type { KnownAddressType } from "./KnownAddressType.ts"

export interface KnownAddress {
    type: KnownAddressType
    name: string
    address: string
}