import type { UserRole } from "./CoreSwaggerTypes.ts"
import type { AuthSession } from "./AuthSession.ts"

export interface UseAuthStoreResult {
    session?: AuthSession
    refreshToken?: string
    setSession: (accessToken: string, expiresIn: number, roles: UserRole[]) => void
    setRefreshToken: (refreshToken: string, refreshExpiresIn: number) => void
}
