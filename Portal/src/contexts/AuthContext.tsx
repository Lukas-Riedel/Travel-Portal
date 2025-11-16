import { createContext, useCallback, useContext, useMemo, type ReactNode } from "react"
import { getIamResponseWithCredentials } from "../clients/iamClient.ts"
import { useAuthStore } from "../hooks/useAuthStore.ts"
import type { UseAuthResult } from "../types/UseAuthResult.ts"
import type { Credentials } from "../types/Credentials.ts"
import { jwtDecode } from "jwt-decode"

const AuthContext = createContext<UseAuthResult | undefined>(undefined)

export const AuthProvider = ({ children }: { children: ReactNode }) => {
    const { accessToken, logout, setIamResponse } = useAuthStore()

    const login = useCallback(async ({ username, password }: Credentials) => {
        if (typeof Android !== "undefined" && Android.login) {
            Android.login(username, password)
        }

        getIamResponseWithCredentials(username, password).then(setIamResponse)
    }, [setIamResponse])

    const isAdmin = useMemo<boolean>(() => {
        if (!accessToken) {
            return false
        }

        try {
            // TODO: Introduce an interface for the JWT token type.
            const decodedAccessToken = jwtDecode<any>(accessToken)
            return decodedAccessToken?.resource_access?.[import.meta.env.VITE_IAM_APP_CLIENT_ID]?.roles?.includes("ADMIN") || false
        }
        catch (e) {
            return false
        }
    }, [accessToken])

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

export const useAuth = (): UseAuthResult => useContext(AuthContext)