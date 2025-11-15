import React, { createContext, useContext } from "react"
import { v4 as uuidv4 } from "uuid"
import { listConfigurationEntries, replaceConfigurationEntry } from "../clients/coreClient.ts"
import type { UseConfigurationResult } from "../types/UseConfigurationResult.ts"
import { useCache } from "../hooks/useCache.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "../hooks/useQuery.ts"

const ConfigContext = createContext<UseConfigurationResult | undefined>(undefined)

const deviceIdCache = useCache<string>("ConfigContext:deviceId")

const deviceId = (() => {
    let id = deviceIdCache.get()
    if (!id) {
        id = uuidv4()
        deviceIdCache.set(id, Number.MAX_SAFE_INTEGER)
    }
    return id
})()

export function ConfigurationProvider({ children }: { children: React.ReactNode }) {
    const { response, refetchResponse } = useQuery({
        queryKey: ["listConfigurationEntries"],
        queryFn: listConfigurationEntries,
        staleTime: ONE_DAY_SECONDS * 1000
    })

    return (
        <ConfigContext.Provider value={{
            configuration: response,
            deviceId,
            updateConfigurationEntry: (key: string, value: any) => replaceConfigurationEntry(key, value).then(refetchResponse)
        }}>
            {children}
        </ConfigContext.Provider>
    )
}

export const useConfiguration = (): UseConfigurationResult => useContext(ConfigContext)
