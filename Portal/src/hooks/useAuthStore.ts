import { create } from "zustand"

import type { IamResponse } from "../types/CoreSwaggerTypes.ts"
import type { UseAuthStoreResult } from "../types/UseAuthStoreResult.ts"
import { useCache } from "./useCache.ts"

// eslint-disable-next-line react-hooks/rules-of-hooks
const accessTokenCache = useCache<string>("useAuthStore:accessToken")
// eslint-disable-next-line react-hooks/rules-of-hooks
const refreshTokenCache = useCache<string>("useAuthStore:refreshToken")

export const useAuthStore = create<UseAuthStoreResult>(set => ({
    accessToken: accessTokenCache.get() ?? undefined,
    refreshToken: refreshTokenCache.get() ?? undefined,
    setIamResponse: (iamResponse: IamResponse) => {
        accessTokenCache.set(iamResponse.accessToken, iamResponse.expiresIn)
        if (iamResponse.refreshToken != null && iamResponse.refreshExpiresIn != null) {
            refreshTokenCache.set(iamResponse.refreshToken, iamResponse.refreshExpiresIn)
        }

        set(iamResponse)
    }
}))