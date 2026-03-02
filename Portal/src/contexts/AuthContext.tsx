import { createContext, useCallback, useContext, useMemo, type ReactNode } from "react"
import { getIamResponseWithCredentials } from "../clients/iamClient.ts"
import { useAuthStore } from "../hooks/useAuthStore.ts"
import type { UseAuthResult } from "../types/UseAuthResult.ts"
import type { Credentials } from "../types/Credentials.ts"
import { jwtDecode } from "jwt-decode"
import { GUEST_CREDENTIALS } from "../utils/authenticationUtils.ts"
import type { UserRole } from "../types/CoreSwaggerTypes.ts"

const AuthContext = createContext<UseAuthResult | undefined>(undefined)

export const AuthProvider = ({ children }: { children: ReactNode }) => {
    const { accessToken, setIamResponse } = useAuthStore()

    const login = useCallback(async ({ username, password }: Credentials) => {
        if (typeof Android !== "undefined" && Android.login) {
            Android.login(username, password)
        }

        getIamResponseWithCredentials(username, password).then(setIamResponse)
    }, [setIamResponse])

    const isLoggedIn = useMemo(() => {
        if (!accessToken) {
            return false
        }

        try {
            // TODO: Introduce an interface for the JWT token type.
            const decodedAccessToken = jwtDecode<any>(accessToken)
            return decodedAccessToken?.preferred_username && decodedAccessToken?.preferred_username !== GUEST_CREDENTIALS.username
        }
        catch {
            return false
        }
    }, [accessToken])

    const userRoles = useMemo(() => {
        if (!accessToken) {
            return []
        }

        try {
            // TODO: Introduce an interface for the JWT token type.
            const decodedAccessToken = jwtDecode<any>(accessToken)
            return decodedAccessToken?.resource_access?.[window.env?.VITE_IAM_APP_CLIENT_ID || import.meta.env.VITE_IAM_APP_CLIENT_ID]?.roles || []
        }
        catch {
            return []
        }
    }, [accessToken])

    const username = useMemo(() => {
        if (!accessToken) {
            return null
        }

        try {
            // TODO: Introduce an interface for the JWT token type.
            const decodedAccessToken = jwtDecode<any>(accessToken)
            return decodedAccessToken?.name
        }
        catch {
            return null
        }
    }, [accessToken])

    const hasRole = useCallback((role: UserRole) => {
        if (userRoles.includes(role)) {
            return true
        }
        
        const roleString = role as string
        if (roleString?.endsWith(".read")) {
            return userRoles.includes(roleString.replace(".read", ".edit"))
        }

        return false
    }, [userRoles])

    return (
        <AuthContext.Provider value={{
            accessToken,
            hasRole,
            isLoggedIn,
            username: isLoggedIn && username,
            login,
            logout: () => login(GUEST_CREDENTIALS)
        }}>
            {children}
        </AuthContext.Provider>
    )
}

export const useAuth = (): UseAuthResult => useContext(AuthContext)