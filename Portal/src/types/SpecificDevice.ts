import type { Device } from "./CoreSwaggerTypes.ts"

export interface SpecificDevice<T> extends Omit<Device, "data"> {
    data: T
}