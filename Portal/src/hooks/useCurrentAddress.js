import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"
import { useEffect, useState } from "react"
import { useVisitedAirports } from "./useVisitedAirports"
import { getEuclideanDistance } from "../utils/helpers"

const airportRadius = 3.0

export const useCurrentAddress = (knownAddresses = []) => {
    const { getCurrentAddress, getCoordinates } = useApi()
    const { isAdmin } = useAuth()
    const [currentAddress, setCurrentAddress] = useState(null)
    const visitedAirports = useVisitedAirports()

    const query = useQuery({
        queryKey: ["getCurrentAddress"],
        queryFn: getCurrentAddress,
        staleTime: isAdmin ? 0 : 1000 * 60 * 15
    })

    const getCachedCoordinates = async (address) => {
        const cachedCoordinates = localStorage.getItem(address)

        if (cachedCoordinates) {
            return Promise.resolve(JSON.parse(cachedCoordinates))
        }

        return getCoordinates(address).then(coordinates => {
            localStorage.setItem(address, JSON.stringify(coordinates))
            return coordinates
        })
    }

    useEffect(() => {
        if (!query.data) {
            return
        }

        const resolveCurrentAddress = async () => {
            const currentCoordinates = await getCachedCoordinates(query.data.address)

            for (const visitedAirport of visitedAirports) {
                const distance = getEuclideanDistance(visitedAirport, currentCoordinates)

                if (distance <= airportRadius) {
                    setCurrentAddress({ name: visitedAirport.longName ?? `Letiště ${visitedAirport.shortName}`, address: visitedAirport.longName ?? `Letiště ${visitedAirport.code}` })
                    return
                }
            }

            for (const knownAddress of knownAddresses) {
                const knownAddressLocation = await getCachedCoordinates(knownAddress.address)
                const distance = getEuclideanDistance(knownAddressLocation, currentCoordinates)

                if (knownAddress.radius && distance <= knownAddress.radius) {
                    setCurrentAddress(knownAddress)
                    return
                }
            }

            setCurrentAddress({ name: query.data.address, address: query.data.address })
        }

        resolveCurrentAddress()
    }, [query.data?.address, knownAddresses.length, visitedAirports.length])

    // TODO: Map to Address object
    return currentAddress && { ...currentAddress, lastUpdate: query.data.lastUpdate }
}