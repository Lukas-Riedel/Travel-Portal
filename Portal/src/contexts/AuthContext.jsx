import { createContext, useContext, useEffect } from "react"
import { logout, setIamResponse, useAuthStore } from "../hooks/useAuthStore"
import { refreshAccessToken } from "../clients/coreClient"
import { getIamResponseWithCredentials } from "../clients/iamClient"

const AuthContext = createContext()
const accessTokenRefreshThreshold = 60 * 1000

export const AuthProvider = ({ children }) => {
    const accessToken = useAuthStore(state => state.getAccessToken())
    const iamResponse = useAuthStore(state => state.iamResponse)
    const isAdmin = useAuthStore(state => state.isAdmin())

    const login = async ({ username, password }) => {
        if (typeof Android !== "undefined" && Android.login) {
            Android.login(username, password)
        }

        getIamResponseWithCredentials(username, password).then(setIamResponse)
    }

    useEffect(() => {
        if (!iamResponse) {
            return
        }

        const delay = iamResponse.expiration - Date.now() - accessTokenRefreshThreshold
        if (delay <= 0) {
            refreshAccessToken()
            return
        }

        const timeout = setTimeout(() => {
            refreshAccessToken()
        }, delay)

        return () => clearTimeout(timeout)
    })

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