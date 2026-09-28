import type { AppConfiguration } from "./CoreSwaggerTypes.ts"

export interface UseConfigurationResult {
    configuration: AppConfiguration | null
    deviceId: string
    updateConfigurationEntry: (key: string, value: unknown) => Promise<AppConfiguration>
}