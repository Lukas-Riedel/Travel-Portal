import { defineConfig, loadEnv } from "vite"
import react from "@vitejs/plugin-react"
import { VitePWA } from "vite-plugin-pwa"

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            react(),
            VitePWA({
                strategies: "injectManifest",
                srcDir: "src",
                filename: "firebase-messaging-sw.js",
                injectManifest: {
                    globPatterns: ["**/*.{js,css,html,ico,png,svg}"],
                    maximumFileSizeToCacheInBytes: 4 * 1024 * 1024
                },
                workbox: {
                    maximumFileSizeToCacheInBytes: 4 * 1024 * 1024
                },
                manifest: {
                    name: "Osobní portál",
                    short_name: "Osobní portál",
                    start_url: ".",
                    display: "standalone",
                    background_color: "#ffffff",
                    theme_color: "#55acee",
                    icons: [
                        { src: "icon-192.png", sizes: "192x192", type: "image/png" },
                        { src: "icon-512.png", sizes: "512x512", type: "image/png" }
                    ]
                }
            })
        ],
        define: {
            __VITE_PORTAL_BASE_URL__: env.VITE_PORTAL_BASE_URL ? JSON.stringify(env.VITE_PORTAL_BASE_URL) : "self.env.VITE_PORTAL_BASE_URL",
            __VITE_FIREBASE_API_KEY__: env.VITE_FIREBASE_API_KEY ? JSON.stringify(env.VITE_FIREBASE_API_KEY) : "self.env.VITE_FIREBASE_API_KEY",
            __VITE_FIREBASE_AUTH_DOMAIN__: env.VITE_FIREBASE_AUTH_DOMAIN ? JSON.stringify(env.VITE_FIREBASE_AUTH_DOMAIN) : "self.env.VITE_FIREBASE_AUTH_DOMAIN",
            __VITE_FIREBASE_PROJECT_ID__: env.VITE_FIREBASE_PROJECT_ID ? JSON.stringify(env.VITE_FIREBASE_PROJECT_ID) : "self.env.VITE_FIREBASE_PROJECT_ID",
            __VITE_FIREBASE_STORAGE_BUCKET__: env.VITE_FIREBASE_STORAGE_BUCKET ? JSON.stringify(env.VITE_FIREBASE_STORAGE_BUCKET) : "self.env.VITE_FIREBASE_STORAGE_BUCKET",
            __VITE_FIREBASE_MESSAGING_SENDER_ID__: env.VITE_FIREBASE_MESSAGING_SENDER_ID ? JSON.stringify(env.VITE_FIREBASE_MESSAGING_SENDER_ID) : "self.env.VITE_FIREBASE_MESSAGING_SENDER_ID",
            __VITE_FIREBASE_APP_ID__: env.VITE_FIREBASE_APP_ID ? JSON.stringify(env.VITE_FIREBASE_APP_ID) : "self.env.VITE_FIREBASE_APP_ID",
            __VITE_FIREBASE_MEASUREMENT_ID__: env.VITE_FIREBASE_MEASUREMENT_ID ? JSON.stringify(env.VITE_FIREBASE_MEASUREMENT_ID) : "self.env.VITE_FIREBASE_MEASUREMENT_ID"
        }
    }
})
