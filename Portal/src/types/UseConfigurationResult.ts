import type { AppConfiguration } from "./AppConfiguration.ts"

export interface UseConfigurationResult {
    configuration?: AppConfiguration
    deviceId: string
    updateConfigurationEntry: (key: string, value: unknown) => Promise<AppConfiguration>
}