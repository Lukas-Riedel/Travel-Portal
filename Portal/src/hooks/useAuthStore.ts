import { create } from "zustand"

import type { UserRole } from "../types/CoreSwaggerTypes.ts"
import type { AuthSession } from "../types/AuthSession.ts"
import type { UseAuthStoreResult } from "../types/UseAuthStoreResult.ts"
import { useCache } from "./useCache.ts"

// eslint-disable-next-line react-hooks/rules-of-hooks
const sessionCache = useCache<AuthSession>("useAuthStore:session")
// eslint-disable-next-line react-hooks/rules-of-hooks
const refreshTokenCache = useCache<string>("useAuthStore:refreshToken")

export const useAuthStore = create<UseAuthStoreResult>(set => ({
    session: sessionCache.get() ?? undefined,
    refreshToken: refreshTokenCache.get() ?? undefined,
    setSession: (accessToken: string, expiresIn: number, roles: UserRole[]) => {
        const session: AuthSession = { accessToken, roles }
        sessionCache.set(session, expiresIn)
        set({ session })
    },
    setRefreshToken: (refreshToken: string, refreshExpiresIn: number) => {
        refreshTokenCache.set(refreshToken, refreshExpiresIn)
        set({ refreshToken })
    },
}))
