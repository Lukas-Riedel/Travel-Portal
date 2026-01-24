import type { UserRole } from "./CoreSwaggerTypes.ts"
import type { Credentials } from "./Credentials.ts"

export interface UseAuthResult {
    accessToken?: string
    hasRole: (role: UserRole) => boolean
    isLoggedIn: boolean
    login: (credentials: Credentials) => Promise<void>
    logout: () => Promise<void>
}