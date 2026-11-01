import { jwtDecode, type JwtPayload } from "jwt-decode"
import { createContext, type ReactNode, useCallback, useContext, useMemo } from "react"

import { getIamResponseWithCredentials, getUserRoles } from "../clients/iamClient.ts"
import { useAuthStore } from "../hooks/useAuthStore.ts"
import type { UserRole } from "../types/CoreSwaggerTypes.ts"
import type { Credentials } from "../types/Credentials.ts"
import type { UseAuthResult } from "../types/UseAuthResult.ts"
import { GUEST_CREDENTIALS } from "../utils/authenticationUtils.ts"

interface AccessTokenPayload extends JwtPayload {
    preferred_username?: string
    name?: string
}

const AuthContext = createContext<UseAuthResult | undefined>(undefined)

interface AuthProviderProps {
    children: ReactNode
}

export const AuthProvider = ({ children }: AuthProviderProps) => {
    const { session, setSession, setRefreshToken } = useAuthStore()

    const accessToken = session?.accessToken
    const roles = session?.roles ?? []

    const login = useCallback(async ({ username, password }: Credentials) => {
        if (typeof Android !== "undefined" && Android.login) {
            Android.login(username, password)
        }

        const iamResponse = await getIamResponseWithCredentials(username, password)
        const fetchedRoles = await getUserRoles(jwtDecode<AccessTokenPayload>(iamResponse.accessToken).sub!, iamResponse.accessToken)
        setSession(iamResponse.accessToken, iamResponse.expiresIn, fetchedRoles)
        if (iamResponse.refreshToken !== undefined && iamResponse.refreshExpiresIn !== undefined) {
            setRefreshToken(iamResponse.refreshToken, iamResponse.refreshExpiresIn)
        }
    }, [setSession, setRefreshToken])

    const decodedAccessToken = useMemo(() => {
        if (!accessToken) {
            return null
        }

        try {
            return jwtDecode<AccessTokenPayload>(accessToken)
        }
        catch {
            return null
        }
    }, [accessToken])

    const isLoggedIn = useMemo(() =>
        !!(decodedAccessToken?.preferred_username && decodedAccessToken.preferred_username !== GUEST_CREDENTIALS.username),
    [decodedAccessToken])

    const username = useMemo(() => decodedAccessToken?.name ?? null, [decodedAccessToken])

    const hasRole = useCallback((role: UserRole) => {
        if (roles.includes(role)) {
            return true
        }

        const roleString = role as string
        if (roleString?.endsWith(".read")) {
            return roles.includes(roleString.replace(".read", ".edit") as UserRole)
        }

        return false
    }, [roles])

    return (
        <AuthContext.Provider value={{
            accessToken,
            hasRole,
            isLoggedIn,
            username: isLoggedIn ? username ?? undefined : undefined,
            login,
            logout: () => login(GUEST_CREDENTIALS)
        }}>
            {children}
        </AuthContext.Provider>
    )
}

export const useAuth = (): UseAuthResult => {
    const context = useContext(AuthContext)
    if (!context) {
        throw new Error("useAuth must be used within AuthProvider")
    }
    return context
}