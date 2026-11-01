import type { UserRole } from "./CoreSwaggerTypes.ts"

export interface AuthSession {
    accessToken: string
    roles: UserRole[]
}
