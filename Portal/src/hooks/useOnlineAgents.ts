import { DeviceType } from "../types/CoreSwaggerTypes"
import { isDeviceOnline } from "../utils/deviceUtils"
import { useDevices } from "./useDevices"

export const useOnlineAgents = () => useDevices({ type: DeviceType.Agent })?.filter(isDeviceOnline)