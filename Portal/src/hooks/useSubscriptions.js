import { useQuery } from "@tanstack/react-query"
import { useAuth } from "../contexts/AuthContext"
import { createSubscription, listSubscriptions, removeSubscription } from "../clients/coreClient"

export const useSubscriptions = () => {
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listSubscriptions"],
        queryFn: listSubscriptions,
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 12
    })

    const refetchSubscriptions = _ => query.refetch()

    return {
        // TODO: Map to Statistics objects
        subscriptions: query.data,
        createSubscription: (description, value, currency, expiration) => createSubscription(description, value, currency, expiration).then(refetchSubscriptions),
        removeSubscription: subscriptionId => removeSubscription(subscriptionId).then(refetchSubscriptions)
    }
}