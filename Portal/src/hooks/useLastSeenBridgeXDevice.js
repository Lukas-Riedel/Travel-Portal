import { useEffect, useMemo, useState } from "react"
import { useVisitedAirports } from "./useVisitedAirports"
import { getCachedCoordinates, getEuclideanDistance } from "../utils/helpers"
import { useDevices } from "./useDevices"
import { getCoordinates } from "../clients/coreClient"

const airportRadius = 3.0

export const useLastSeenBridgeXDevice = (knownAddresses = []) => {
    const [currentAddress, setCurrentAddress] = useState(null)
    const visitedAirports = useVisitedAirports()
    const devices = useDevices({ type: "bridgex" })

    const lastSeenDevice = useMemo(() => devices?.find(device => device.data?.address && device.data?.latitude && device.data?.longitude), [devices])

    useEffect(() => {
        if (!lastSeenDevice) {
            return
        }

        const resolveCurrentAddress = async () => {
            for (const visitedAirport of visitedAirports) {
                const distance = getEuclideanDistance(visitedAirport, lastSeenDevice.data)

                if (distance <= airportRadius) {
                    setCurrentAddress({ name: visitedAirport.longName ?? `Letiště ${visitedAirport.shortName}`, address: visitedAirport.longName ?? `Letiště ${visitedAirport.code}` })
                    return
                }
            }

            for (const knownAddress of knownAddresses) {
                const knownAddressLocation = await getCachedCoordinates(knownAddress.address, getCoordinates)
                const distance = getEuclideanDistance(knownAddressLocation, lastSeenDevice.data)

                if (knownAddress.radius && distance <= knownAddress.radius) {
                    setCurrentAddress(knownAddress)
                    return
                }
            }

            setCurrentAddress({ name: lastSeenDevice.data.address, address: lastSeenDevice.data.address })
        }

        resolveCurrentAddress()
    }, [lastSeenDevice?.data, knownAddresses.length, visitedAirports?.length])

    return currentAddress && {
        ...currentAddress,
        battery: lastSeenDevice.data.battery,
        timezone: lastSeenDevice.data.timezone,
        latitude: lastSeenDevice.data.latitude,
        longitude: lastSeenDevice.data.longitude,
        lastSeen: lastSeenDevice.lastSeen
    }
}