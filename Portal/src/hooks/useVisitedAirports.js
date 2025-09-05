import { useMemo } from "react"
import { useRegularTrips } from "../hooks/useRegularTrips"

export const useVisitedAirports = () => {
    const trips = useRegularTrips({ include: "flights" })
    const loggedFlights = useMemo(() => trips?.flatMap(trip => trip.flights ?? []).filter(flight => flight.registration), [trips])
    return useMemo(() => [...(loggedFlights?.map(f => f.from) ?? []), ...(loggedFlights?.map(f => f.to) ?? [])]
        .filter((airport, index, self) => airport && self.findIndex(a => a.id === airport.id) === index), [loggedFlights])
}