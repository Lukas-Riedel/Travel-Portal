import type { IamResponse } from "./CoreSwaggerTypes.ts"

export interface UseAuthStoreResult {
    accessToken?: string
    refreshToken?: string
    setIamResponse: (iamResponse: IamResponse) => void
    logout: () => void
}