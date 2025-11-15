export interface UseConfigurationResult {
    configuration?: Record<string, any>
    deviceId: string
    updateConfigurationEntry: (key: string, value: any) => Promise<void>
}