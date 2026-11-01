import { useMemo } from "react"

import type { BridgeXDeviceData } from "../types/BridgeXDeviceData.ts"
import { DeviceType } from "../types/CoreSwaggerTypes.ts"
import type { SpecificDevice } from "../types/SpecificDevice.ts"
import type { UseLastSeenBridgeXDeviceResult } from "../types/UseLastSeenBridgeXDeviceResult.ts"
import { useDevices } from "./useDevices.ts"

export const useLastSeenBridgeXDevice = (): UseLastSeenBridgeXDeviceResult => {
    const devices = useDevices({ type: DeviceType.Bridgex })

    return useMemo(() => devices
        ?.filter(device => device.data && device.data.latitude && device.data.longitude && device.data.address)
        ?.reduce<SpecificDevice<BridgeXDeviceData> | undefined>((lastSeenCandidate, current) => (!lastSeenCandidate || current.lastSeen > lastSeenCandidate.lastSeen ? current as SpecificDevice<BridgeXDeviceData> : lastSeenCandidate), undefined),
    [devices])
}
