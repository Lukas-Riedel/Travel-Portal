import { createContext, useContext, useState, useEffect, useCallback, useMemo } from "react"
import { useIam } from "../hooks/useIam"

const AuthContext = createContext()

export const AuthProvider = ({ children }) => {
    const iam = useIam()
    const [accessToken, setAccessToken] = useState(() => {
        const stored = localStorage.getItem("accessToken")
        return stored ? JSON.parse(stored) : null
    })

    const isAdmin = useMemo(() => accessToken?.roles?.includes("ADMIN") || false, [accessToken])

    const login = async ({ username, password, apiKey }) => {
        const token = apiKey
            ? await iam.getAccessTokenForApiKey(apiKey)
            : await iam.getAccessTokenForUser(username, password)

        token.expiration = Date.now() + token.validity * 1000
        localStorage.setItem("accessToken", JSON.stringify(token))
        setAccessToken(token)
    }

    const logout = () => {
        localStorage.removeItem("accessToken")
        setAccessToken(null)
    }

    const refreshAccessToken = useCallback(async () => {
        if (!accessToken?.refreshToken) {
            logout()
            return
        }

        try {
            const newToken = await iam.getAccessTokenForRefreshToken(accessToken.refreshToken)
            newToken.expiration = Date.now() + newToken.validity * 1000

            localStorage.setItem("accessToken", JSON.stringify(newToken))
            setAccessToken(newToken)
        } catch {
            logout()
        }
    }, [accessToken, iam])

    useEffect(() => {
        if (!accessToken) {
            return
        }

        const refreshThreshold = 60 * 1000
        const delay = accessToken.expiration - Date.now() - refreshThreshold

        if (delay <= 0) {
            refreshAccessToken()
            return
        }

        const timeout = setTimeout(refreshAccessToken, delay)

        return () => clearTimeout(timeout)
    }, [accessToken, refreshAccessToken])

    return (
        <AuthContext.Provider value={{
            accessToken,
            login,
            logout,
            isAdmin
        }}>
            {children}
        </AuthContext.Provider>
    )
}

export const useAuth = () => useContext(AuthContext)
