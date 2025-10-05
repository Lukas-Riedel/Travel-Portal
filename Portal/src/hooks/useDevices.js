import { useQuery } from "@tanstack/react-query"
import { useAuth } from "../contexts/AuthContext"
import { listDevices } from "../clients/coreClient"

export const useDevices = ({ type } = {}) => {
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listDevices", type],
        queryFn: () => listDevices({ type }),
        staleTime: isAdmin ? 0 : 1000 * 60
    })

    // TODO: Map to Device objects
    return query.data
}