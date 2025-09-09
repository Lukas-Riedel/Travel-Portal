import { useMemo } from "react"
import { useRegularTrips } from "../hooks/useRegularTrips"
import { useAirports } from "./useAirports"

export const useVisitedAirports = () => {
    const { airports } = useAirports()
    return useMemo(() => airports?.filter(airport => airport.id) ?? [], [airports])
}