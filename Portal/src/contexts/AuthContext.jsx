import { createContext, useContext } from "react"
import { getIamResponseWithCredentials } from "../clients/iamClient"
import { useAuthStore } from "../hooks/useAuthStore.ts"

const AuthContext = createContext()

export const AuthProvider = ({ children }) => {
    const { accessToken, isAdmin, logout, setIamResponse, } = useAuthStore()

    const login = async ({ username, password }) => {
        if (typeof Android !== "undefined" && Android.login) {
            Android.login(username, password)
        }

        getIamResponseWithCredentials(username, password).then(setIamResponse)
    }

    return (
        <AuthContext.Provider value={{
            accessToken,
            isAdmin,
            login,
            logout
        }}>
            {children}
        </AuthContext.Provider>
    )
}

export const useAuth = () => useContext(AuthContext)