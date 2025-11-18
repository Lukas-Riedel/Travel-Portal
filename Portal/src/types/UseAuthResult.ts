import type { Credentials } from "./Credentials.ts"

export interface UseAuthResult {
    accessToken?: string
    isAdmin: boolean
    login: (credentials: Credentials) => Promise<void>
    logout: () => Promise<void>
}