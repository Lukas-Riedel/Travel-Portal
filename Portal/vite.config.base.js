import { defineConfig } from "vite"
import react from "@vitejs/plugin-react"
import { VitePWA } from "vite-plugin-pwa"

export default defineConfig({
    plugins: [
        react(),
        VitePWA({
            strategies: "injectManifest",
            srcDir: "src",
            filename: "firebase-messaging-sw.js",
            injectManifest: {
                globPatterns: ["**/*.{js,css,html,ico,png,svg}"]
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
        __VITE_PORTAL_BASE_URL__: JSON.stringify(process.env.VITE_PORTAL_BASE_URL),
        __VITE_FIREBASE_API_KEY__: JSON.stringify(process.env.VITE_FIREBASE_API_KEY),
        __VITE_FIREBASE_AUTH_DOMAIN__: JSON.stringify(process.env.VITE_FIREBASE_AUTH_DOMAIN),
        __VITE_FIREBASE_PROJECT_ID__: JSON.stringify(process.env.VITE_FIREBASE_PROJECT_ID),
        __VITE_FIREBASE_STORAGE_BUCKET__: JSON.stringify(process.env.VITE_FIREBASE_STORAGE_BUCKET),
        __VITE_FIREBASE_MESSAGING_SENDER_ID__: JSON.stringify(process.env.VITE_FIREBASE_MESSAGING_SENDER_ID),
        __VITE_FIREBASE_APP_ID__: JSON.stringify(process.env.VITE_FIREBASE_APP_ID),
        __VITE_FIREBASE_MEASUREMENT_ID__: JSON.stringify(process.env.VITE_FIREBASE_MEASUREMENT_ID)
    }
})
