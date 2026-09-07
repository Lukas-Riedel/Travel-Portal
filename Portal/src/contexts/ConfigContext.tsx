import { createContext, type ReactNode,useContext } from "react"
import { v4 as uuidv4 } from "uuid"

import { listConfigurationEntries, replaceConfigurationEntry } from "../clients/coreClient.ts"
import { useCache } from "../hooks/useCache.ts"
import { useQuery } from "../hooks/useQuery.ts"
import type { UseConfigurationResult } from "../types/UseConfigurationResult.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"

const ConfigContext = createContext<UseConfigurationResult | undefined>(undefined)

// eslint-disable-next-line react-hooks/rules-of-hooks
const deviceIdCache = useCache<string>("ConfigContext:deviceId")

const deviceId = (() => {
    let id = deviceIdCache.get()
    if (!id) {
        id = uuidv4()
        deviceIdCache.set(id, Number.MAX_SAFE_INTEGER)
    }
    return id
})()

interface ConfigProviderProps {
    children: ReactNode
}

export function ConfigurationProvider({ children }: ConfigProviderProps) {
    const { response, refetchResponse } = useQuery({
        queryKey: ["listConfigurationEntries"],
        queryFn: listConfigurationEntries,
        staleTime: ONE_DAY_SECONDS * 1000
    })

    return (
        <ConfigContext.Provider value={{
            configuration: response,
            deviceId,
            updateConfigurationEntry: (key: string, value: unknown) => replaceConfigurationEntry(key, value).then(refetchResponse)
        }}>
            {children}
        </ConfigContext.Provider>
    )
}

export const useConfiguration = (): UseConfigurationResult => {
    const context = useContext(ConfigContext)
    if (!context) {
        throw new Error("The useConfiguration hook must be used within ConfigurationProvider.")
    }

    return context
}
