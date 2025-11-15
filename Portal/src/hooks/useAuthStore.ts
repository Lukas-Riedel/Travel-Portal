import { create } from "zustand"
import { useCache } from "./useCache.ts"
import type { IamResponse } from "../types/CoreSwaggerTypes.ts"
import type { UseAuthStoreResult } from "../types/UseAuthStoreResult.ts"

const accessTokenCache = useCache<string>("useAuthStore:accessToken")
const refreshTokenCache = useCache<string>("useAuthStore:refreshToken")

export const useAuthStore = create<UseAuthStoreResult>(set => ({
    accessToken: accessTokenCache.get(),
    refreshToken: refreshTokenCache.get(),
    setIamResponse: (iamResponse: IamResponse) => {
        accessTokenCache.set(iamResponse.accessToken, iamResponse.expiresIn)
        refreshTokenCache.set(iamResponse.refreshToken, iamResponse.refreshExpiresIn)

        set(iamResponse)
    },
    logout: () => {
        if (typeof Android !== "undefined" && Android.logout) {
            Android.logout()
        }

        accessTokenCache.remove()
        refreshTokenCache.remove()

        set({
            accessToken: null,
            refreshToken: null
        })
    }
}))