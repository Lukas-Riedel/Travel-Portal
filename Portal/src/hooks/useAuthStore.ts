import { create } from "zustand"
import { jwtDecode } from "jwt-decode"
import { useCache } from "./useCache.ts"
import type { IamResponse } from "../types/CoreSwaggerTypes.ts"
import type { UseAuthStoreResult } from "../types/UseAuthStoreResult.ts"

const accessTokenCache = useCache<string>("useAuthStore:accessToken")
const refreshTokenCache = useCache<string>("useAuthStore:refreshToken")

export const useAuthStore = create<UseAuthStoreResult>(set => ({
    accessToken: accessTokenCache.get(),
    refreshToken: refreshTokenCache.get(),
    isAdmin: isAdmin(accessTokenCache.get()),
    setIamResponse: (iamResponse: IamResponse) => {
        accessTokenCache.set(iamResponse.accessToken, iamResponse.expiresIn)
        refreshTokenCache.set(iamResponse.refreshToken, iamResponse.refreshExpiresIn)

        set({
            accessToken: iamResponse.accessToken,
            refreshToken: iamResponse.refreshToken,
            isAdmin: isAdmin(iamResponse.accessToken)
        })
    },
    logout: () => {
        if (typeof Android !== "undefined" && Android.logout) {
            Android.logout()
        }

        accessTokenCache.remove()
        refreshTokenCache.remove()

        set({
            accessToken: null,
            refreshToken: null,
            isAdmin: false
        })
    }
}))

function isAdmin(accessToken?: string): boolean {
    if (!accessToken) {
        return false
    }

    try {
        // TODO: Introduce an interface for the JWT token type.
        const decodedAccessToken = jwtDecode<any>(accessToken)
        return decodedAccessToken?.resource_access?.[import.meta.env.VITE_IAM_APP_CLIENT_ID]?.roles?.includes("ADMIN") || false
    }
    catch (e) {
        return false
    }
}