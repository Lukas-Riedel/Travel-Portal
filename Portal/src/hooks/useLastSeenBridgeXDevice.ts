import { useEffect, useMemo, useState } from "react"
import { useAirports } from "./useAirports.ts"
import { getCachedCoordinates } from "../utils/helpers.js"
import { useDevices } from "./useDevices.ts"
import { getCoordinates } from "../clients/coreClient.ts"
import { getEuclideanDistance } from "../utils/geocodingUtils.ts"
import { DeviceType } from "../types/CoreSwaggerTypes.ts"
import type { SpecificDevice } from "../types/SpecificDevice.ts"
import type { BridgeXDeviceData } from "../types/BridgeXDeviceData.ts"
import type { KnownAddress } from "../types/KnownAddress.ts"
import type { Coordinates } from "../types/Coordinates.ts"
import { useTranslation } from "react-i18next"
import type { UseLastSeenBridgeXDeviceResult } from "../types/UseLastSeenBridgeXDeviceResult.ts"
import { KnownAddressType } from "../types/KnownAddressType.ts"

const RADIUSES = {
    [KnownAddressType.Airport]: 3.0,
    [KnownAddressType.Stay]: 0.5,
    [KnownAddressType.Other]: 0.1
}

export const useLastSeenBridgeXDevice = (knownAddresses: KnownAddress[] = []): UseLastSeenBridgeXDeviceResult => {
    const { t } = useTranslation()

    const { airports } = useAirports()
    const devices = useDevices({ type: DeviceType.Bridgex })

    const [currentAddress, setCurrentAddress] = useState<KnownAddress | null>(null)

    const lastSeenBridgeXDevice = useMemo(() => devices
        ?.filter(device => device.data && device.data.latitude && device.data.longitude && device.data.address)
        ?.reduce((lastSeenCandidate, current) => (!lastSeenCandidate || current.lastSeen > lastSeenCandidate.lastSeen ? current : lastSeenCandidate), undefined) as SpecificDevice<BridgeXDeviceData> | undefined, [devices])

    useEffect(() => {
        if (!lastSeenBridgeXDevice?.data || !lastSeenBridgeXDevice.data.latitude || !lastSeenBridgeXDevice.data.longitude) {
            return
        }

        (async () => {
            const candidates = [
                ...knownAddresses.filter(address => address.type === KnownAddressType.Stay),
                ...((airports ?? []).map(airport => ({ type: KnownAddressType.Airport, name: airport.longName ?? t("airport.format", { name: airport.shortName }), address: airport.longName ?? t("airport.format", { name: airport.shortName }) }))),
                ...knownAddresses.filter(address => address.type === KnownAddressType.Airport),
                ...knownAddresses.filter(address => address.type === KnownAddressType.Other)
            ]

            for (const candidate of candidates) {
                const candidateCoordinates = await getCachedCoordinates(candidate.address, getCoordinates)

                if (candidateCoordinates) {
                    const distance = getEuclideanDistance(candidateCoordinates, lastSeenBridgeXDevice.data as Coordinates)

                    if (distance <= RADIUSES[candidate.type]) {
                        setCurrentAddress(candidate)
                        return
                    }
                }
            }

            setCurrentAddress(lastSeenBridgeXDevice.data.address)
        })()
    }, [lastSeenBridgeXDevice?.data, knownAddresses.length, airports?.length])

    return lastSeenBridgeXDevice && {
        ...lastSeenBridgeXDevice,
        data: {
            ...lastSeenBridgeXDevice.data,
            address: currentAddress ?? lastSeenBridgeXDevice.data.address
        }
    }
}