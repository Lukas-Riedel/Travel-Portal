import type { AppConfiguration } from "./AppConfiguration.ts"

export interface UseConfigurationResult {
    configuration: AppConfiguration | null
    deviceId: string
    updateConfigurationEntry: (key: string, value: unknown) => Promise<AppConfiguration>
}