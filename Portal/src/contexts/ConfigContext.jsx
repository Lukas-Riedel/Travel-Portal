import { createContext, useContext } from "react"
import { useApi } from "../hooks/useApi"
import { useQuery } from "@tanstack/react-query"
import { v4 as uuidv4 } from "uuid"

const ConfigContext = createContext()

const deviceId = (() => {
    let id = localStorage.getItem("deviceId")
    if (!id) {
        id = uuidv4()
        localStorage.setItem("deviceId", id)
    }
    return id
})()

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
            deviceId,
            updateConfigurationEntry: (key, value) => replaceConfigurationEntry(key, value).then(refetchConfiguration)
        }}>
            {children}
        </ConfigContext.Provider>
    )
}

export const useConfiguration = () => useContext(ConfigContext)
