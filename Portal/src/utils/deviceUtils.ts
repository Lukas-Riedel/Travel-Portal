import { DeviceType, type Device } from "../types/CoreSwaggerTypes.ts"
import { getCurrentTimestamp } from "./timeUtils.ts"

const AGENT_ONLINE_STATUS_THRESHOLD_SECONDS = 60

export function isDeviceOnline(device: Device): boolean {
    if (device.type === DeviceType.Agent) {
        return device.lastSeen + AGENT_ONLINE_STATUS_THRESHOLD_SECONDS > getCurrentTimestamp()
    }

    // TODO: Implement the logic for other device types.
    return true
}