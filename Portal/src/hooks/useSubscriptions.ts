import { createSubscription, listSubscriptions, removeSubscription } from "../clients/coreClient.ts"
import type { UseSubscriptionsResult } from "../types/UseSubscriptionsResult.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

export const useSubscriptions = (): UseSubscriptionsResult => {
    const { response, refetchResponse } = useQuery({
        queryKey: ["listSubscriptions"],
        queryFn: listSubscriptions,
        staleTime: ONE_DAY_SECONDS * 1000
    })

    return {
        subscriptions: response,
        createSubscription: (description: string, value: number, currency: string, expiration: number) =>
            createSubscription(description, value, currency, expiration).then(refetchResponse),
        removeSubscription: (subscriptionId: string) => removeSubscription(subscriptionId).then(refetchResponse)
    }
}