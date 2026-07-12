export { }

declare global {
    interface ImportMetaEnv {
        readonly VITE_SW_VERSION: string
        readonly VITE_APP_NAME: string
        readonly VITE_DEFAULT_USERNAME: string
        readonly VITE_DEFAULT_PASSWORD: string
        readonly VITE_PORTAL_BASE_URL: string
        readonly VITE_CORE_BASE_URL: string
        readonly VITE_IAM_BASE_URL: string
        readonly VITE_IAM_APP_CLIENT_ID: string
        readonly VITE_FRONTEND_GOOGLE_MAPS_API_KEY: string
        readonly VITE_FIREBASE_API_KEY: string
        readonly VITE_FIREBASE_VAPID_KEY: string
        readonly VITE_FIREBASE_AUTH_DOMAIN: string
        readonly VITE_FIREBASE_PROJECT_ID: string
        readonly VITE_FIREBASE_STORAGE_BUCKET: string
        readonly VITE_FIREBASE_MESSAGING_SENDER_ID: string
        readonly VITE_FIREBASE_APP_ID: string
        readonly VITE_FIREBASE_MEASUREMENT_ID: string
        readonly VITE_MAXIMUM_ALLOWED_TIMESTAMP: int
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv
    }

    interface Window {
        readonly env?: Partial<ImportMetaEnv>
    }

    const Android: {
        login?: (username: string, password: string) => void
        share?: (title: string, url: string) => void
    } | undefined
}