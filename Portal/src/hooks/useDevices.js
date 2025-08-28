import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"

export const useDevices = ({ type } = {}) => {
    const { listDevices } = useApi()
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listDevices", type],
        queryFn: () => listDevices({ type }),
        staleTime: isAdmin ? 0 : 1000 * 60
    })

    // TODO: Map to Device objects
    return query.data
}