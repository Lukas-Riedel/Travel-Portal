import { createContext, useContext, useState, useEffect } from "react"
import { useApi } from "../hooks/useApi"
import { useAuth } from "./AuthContext"

const ConfigContext = createContext()

export function ConfigurationProvider({ children }) {
    const { accessToken } = useAuth()
    const api = useApi()
    const [configuration, setConfiguration] = useState(null)

    useEffect(() => {
        if (accessToken) {
            api.listConfigurationEntries("public").then(setConfiguration)
        }
    }, [api, accessToken])

    return (
        <ConfigContext.Provider value={configuration}>
            {children}
        </ConfigContext.Provider>
    )
}

export const useConfiguration = () => useContext(ConfigContext)
