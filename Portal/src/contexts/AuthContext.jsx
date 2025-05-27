import { createContext, useContext, useState, useEffect, useCallback } from "react"
import { useIam } from "../hooks/useIam"

const AuthContext = createContext()

export const AuthProvider = ({ children }) => {
    const iam = useIam()
    const [accessToken, setAccessToken] = useState(JSON.parse(localStorage.getItem("accessToken") || "null"))

    const login = async ({ username, password, apiKey }) => {
        const accessToken = apiKey
            ? await iam.getAccessTokenForApiKey(apiKey)
            : await iam.getAccessTokenForUser(username, password)
        accessToken.expiration = Date.now() + accessToken.validity * 1000

        localStorage.setItem("accessToken", JSON.stringify(accessToken))
        setAccessToken(accessToken)
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
            const { newAccessToken } = await iam.getAccessTokenForRefreshToken(accessToken.refreshToken)
            newAccessToken.expiration = Date.now() + accessToken.validity * 1000

            localStorage.setItem("accessToken", JSON.stringify(newAccessToken))
            setToken(newAccessToken)
        }
        catch {
            logout()
        }
    }, [accessToken])

    useEffect(() => {
        if (!accessToken || accessToken.expiration < Date.now()) {
            refreshAccessToken()
        }
    }, [accessToken, refreshAccessToken])

    return (
        <AuthContext.Provider value={{ accessToken, login, logout, isAdmin: () => accessToken?.roles?.includes("ADMIN") || false }}>
            {children}
        </AuthContext.Provider>
    )
}

export const useAuth = () => useContext(AuthContext)