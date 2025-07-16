import { useAuth } from "../contexts/AuthContext"
import { useRegularPlaces } from "./useRegularPlaces"
import { getMaxEndTimestamp } from "../utils/helpers"

export const useTimeFilteredRegularPlaces = ({ tripId, categoryId, labelId, year, include, sort } = {}) => {
    const { isAdmin } = useAuth()
    
    return useRegularPlaces({ tripId, categoryId, labelId, year, include, sort, maxEnd: getMaxEndTimestamp(isAdmin) })
}