import { useAuth } from "../contexts/AuthContext"
import { useRegularPlaces } from "./useRegularPlaces"
import { getMaxEndTimestamp } from "../utils/helpers"

export const useTimeFilteredRegularPlaces = ({ tripId, categoryId, labelName, year, include, sort } = {}) => {
    const { isAdmin } = useAuth()
    return useRegularPlaces({ tripId, categoryId, labelName, year, include, sort, maxEnd: getMaxEndTimestamp(isAdmin) })
}