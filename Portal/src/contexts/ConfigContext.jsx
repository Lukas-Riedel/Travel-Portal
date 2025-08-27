import { createContext, useContext } from "react"
import { useApi } from "../hooks/useApi"
import { useQuery } from "@tanstack/react-query"

const ConfigContext = createContext()

export function ConfigurationProvider({ children }) {
    const { listConfigurationEntries, replaceConfigurationEntry } = useApi()

    const query = useQuery({
        queryKey: ["listConfigurationEntries", "public"],
        queryFn: () => listConfigurationEntries("public"),
        staleTime: 1000 * 60 * 60 * 24
    })

    const refetchConfiguration = _ => query.refetch()

    return (
        <ConfigContext.Provider value={{
            configuration: query.data, 
            updateConfigurationEntry: (key, value) => replaceConfigurationEntry(key, value).then(refetchConfiguration)
        }}>
            {children}
        </ConfigContext.Provider>
    )
}

export const useConfiguration = () => useContext(ConfigContext)
