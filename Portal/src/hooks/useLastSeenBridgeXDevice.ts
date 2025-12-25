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

const AIRPORT_RADIUS_KILOMETERS = 3.0
const DEFAULT_RADIUS_KILOMETERS = 0.1

export const useLastSeenBridgeXDevice = (knownAddresses: KnownAddress[] = []): UseLastSeenBridgeXDeviceResult => {
    const { t } = useTranslation()

    const { airports } = useAirports()
    const devices = useDevices({ type: DeviceType.Bridgex })

    const [currentAddress, setCurrentAddress] = useState<KnownAddress | null>(null)

    const lastSeenBridgeXDevice = useMemo(() => devices?.reduce((lastSeenCandidate, current) => (!lastSeenCandidate || current.lastSeen > lastSeenCandidate.lastSeen ? current : lastSeenCandidate), undefined) as SpecificDevice<BridgeXDeviceData> | undefined, [devices])

    useEffect(() => {
        if (!lastSeenBridgeXDevice?.data) {
            return
        }

        (async () => {
            if (lastSeenBridgeXDevice.data.latitude && lastSeenBridgeXDevice.data.longitude) {
                for (const visitedAirport of (airports ?? [])) {
                    const distance = getEuclideanDistance(visitedAirport as Coordinates, lastSeenBridgeXDevice.data as Coordinates)

                    if (distance <= AIRPORT_RADIUS_KILOMETERS) {
                        setCurrentAddress({
                            name: visitedAirport.longName ?? t("airport.format", { name: visitedAirport.shortName }),
                            address: visitedAirport.longName ?? t("airport.format", { name: visitedAirport.shortName })
                        })
                        return
                    }
                }

                for (const knownAddress of knownAddresses) {
                    const knownAddressCoordinates = await getCachedCoordinates(knownAddress.address, getCoordinates)

                    if (knownAddressCoordinates) {
                        const distance = getEuclideanDistance(knownAddressCoordinates, lastSeenBridgeXDevice.data as Coordinates)

                        if (distance <= (knownAddress?.radius ?? DEFAULT_RADIUS_KILOMETERS)) {
                            setCurrentAddress(knownAddress)
                            return
                        }
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