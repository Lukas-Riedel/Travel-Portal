import { create } from "zustand"
import { jwtDecode } from "jwt-decode"

const iamResponseStorageKey = "iamResponse"

const loadIamResponse = () => {
    const storedIamResponse = localStorage.getItem(iamResponseStorageKey)
    if (!storedIamResponse) {
        return null
    }

    const parsedIamResponse = JSON.parse(storedIamResponse)
    if (parsedIamResponse.refreshExpiration < Date.now()) {
        localStorage.removeItem(iamResponseStorageKey)
        return null
    }

    return parsedIamResponse
}

// TODO: Rework not to use local storage.
export const useAuthStore = create((set, get) => ({
    iamResponse: loadIamResponse(),
    setIamResponse: rawIamResponse => {
        const newIamResponse = {
            data: rawIamResponse,
            expiration: Date.now() + rawIamResponse.expiresIn * 1000,
            refreshExpiration: Date.now() + (rawIamResponse.refreshExpiresIn > 0
                ? rawIamResponse.refreshExpiresIn * 1000 : Number.MAX_SAFE_INTEGER)
        }

        localStorage.setItem(iamResponseStorageKey, JSON.stringify(newIamResponse))
        set({ iamResponse: newIamResponse })
        return newIamResponse
    },
    logout: () => {
        if (typeof Android !== "undefined" && Android.logout) {
            Android.logout()
        }

        localStorage.removeItem(iamResponseStorageKey)
        set({ iamResponse: null })
    },
    getAccessToken: () => {
        return get().iamResponse?.data?.accessToken
    },
    getRefreshToken: () => {
        return get().iamResponse?.data?.refreshToken
    },
    isAdmin: () => {
        const accessToken = get().getAccessToken()
        if (!accessToken) {
            return false
        }

        try {
            const decodedAccessToken = jwtDecode(accessToken)
            return decodedAccessToken?.resource_access?.[import.meta.env.VITE_IAM_APP_CLIENT_ID]?.roles?.includes("ADMIN") || false
        }
        catch (e) {
            return false
        }
    },
}))

export const {
    setIamResponse,
    logout,
    getAccessToken,
    getRefreshToken
} = useAuthStore.getState()