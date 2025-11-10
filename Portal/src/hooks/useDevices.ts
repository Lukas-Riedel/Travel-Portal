import { listDevices } from "../clients/coreClient.ts"
import type { DeviceType } from "../types/CoreSwaggerTypes.ts"
import type { UseDevicesResults } from "../types/UseDevicesResult.ts"
import { ONE_MINUTE_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

export const useDevices = ({ type }: { type?: DeviceType } = {}): UseDevicesResults => {
    const { response } = useQuery({
        queryKey: ["listDevices", type],
        queryFn: () => listDevices({ type }),
        staleTime: ONE_MINUTE_SECONDS * 1000
    })

    return response
}