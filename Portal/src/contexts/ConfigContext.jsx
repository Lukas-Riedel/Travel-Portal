import { createContext, useContext, useState, useEffect } from "react"
import { useApi } from "../hooks/useApi"
import { useQuery } from "@tanstack/react-query"

const ConfigContext = createContext()

export function ConfigurationProvider({ children }) {
    const api = useApi()

    const { data: configuration = null } = useQuery({
        queryKey: ["publicConfiguration"],
        queryFn: () => api.listConfigurationEntries("public"),
        staleTime: 1000 * 60 * 60,
    })

    return (
        <ConfigContext.Provider value={configuration}>
            {children}
        </ConfigContext.Provider>
    )
}

export const useConfiguration = () => useContext(ConfigContext)
