import { createContext, useContext, useState, useEffect, useCallback, useMemo } from "react"
import { useIam } from "../hooks/useIam"
import { jwtDecode } from "jwt-decode"

const AuthContext = createContext()

export const AuthProvider = ({ children }) => {
    const { getIamResponseWithCredentials, getIamResponseWithRefresh } = useIam()
    const [iamResponse, setIamResponse] = useState(() => {
        const storedIamResponse = localStorage.getItem("iamResponse")
        if (!storedIamResponse) {
            return null
        }

        const parsedIamResponse = JSON.parse(storedIamResponse)
        if (parsedIamResponse.refreshExpiration < Date.now()) {
            return null
        }

        return parsedIamResponse
    })

    const isAdmin = useMemo(() => {
        if (!iamResponse?.data?.accessToken) {
            return false
        }

        try {
            const decodedAccessToken = jwtDecode(iamResponse.data.accessToken)
            return decodedAccessToken?.resource_access?.[import.meta.env.VITE_IAM_CLIENT_ID]?.roles?.includes("ADMIN") || false
        }
        catch (e) {
            return false
        }
    }, [iamResponse])

    const login = async ({ username, password }) => {
        if (typeof Android !== "undefined" && Android.login) {
            Android.login(username, password)
        }

        const rawIamResponse = await getIamResponseWithCredentials(username, password)
        const newIamResponse = {
            data: rawIamResponse,
            expiration: Date.now() + rawIamResponse.expiresIn * 1000,
            refreshExpiration: Date.now() + rawIamResponse.refreshExpiresIn * 1000,
        }

        localStorage.setItem("iamResponse", JSON.stringify(newIamResponse))
        setIamResponse(newIamResponse)
    }

    const logout = () => {
        if (typeof Android !== "undefined" && Android.logout) {
            Android.logout()
        }

        localStorage.removeItem("iamResponse")
        setIamResponse(null)
    }

    const refreshAccessToken = useCallback(async () => {
        if (!iamResponse?.data?.refreshToken) {
            logout()
            return
        }

        try {
            const rawIamResponse = await getIamResponseWithRefresh(iamResponse.data.refreshToken)
            const newIamResponse = {
                data: rawIamResponse,
                expiration: Date.now() + rawIamResponse.expiresIn * 1000,
                refreshExpiration: Date.now() + rawIamResponse.refreshExpiresIn * 1000,
            }

            localStorage.setItem("iamResponse", JSON.stringify(newIamResponse))
            setIamResponse(newIamResponse)
        } catch {
            logout()
        }
    }, [iamResponse])

    useEffect(() => {
        if (!iamResponse) {
            return
        }

        const refreshThreshold = 60 * 1000
        const delay = iamResponse.expiration - Date.now() - refreshThreshold

        if (delay <= 0) {
            refreshAccessToken()
            return
        }

        const timeout = setTimeout(refreshAccessToken, delay)

        return () => clearTimeout(timeout)
    }, [iamResponse])

    useEffect(() => {
        if (!iamResponse) {
            return
        }

        const init = async () => {
            try {
                await refreshAccessToken()
            } catch {
                logout()
            }
        }

        init()
    }, [])

    return (
        <AuthContext.Provider value={{
            accessToken: iamResponse?.data?.accessToken,
            login,
            logout,
            isAdmin
        }}>
            {children}
        </AuthContext.Provider>
    )
}

export const useAuth = () => useContext(AuthContext)
