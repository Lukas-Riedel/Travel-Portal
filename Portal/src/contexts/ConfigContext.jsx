import { createContext, useContext } from "react"
import { useApi } from "../hooks/useApi"
import { useQuery } from "@tanstack/react-query"

const ConfigContext = createContext()

export function ConfigurationProvider({ children }) {
    const { listConfigurationEntries } = useApi()

    const configuration = useQuery({
        queryKey: ["listConfigurationEntries", "public"],
        queryFn: () => listConfigurationEntries("public"),
        staleTime: 1000 * 60 * 60 * 24,
    }).data

    return (
        <ConfigContext.Provider value={configuration}>
            {children}
        </ConfigContext.Provider>
    )
}

export const useConfiguration = () => useContext(ConfigContext)
