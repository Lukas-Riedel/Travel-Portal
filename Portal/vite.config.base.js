import { defineConfig } from "vite"
import react from "@vitejs/plugin-react"

export default defineConfig({
    base: process.env.VITE_BASE_PATH || "/",
    plugins: [react()],
    build: {
        rollupOptions: {
            input: {
                main: "index.html",
                sw: "src/firebase-messaging-sw.js"
            },
            output: {
                entryFileNames: chunk => {
                    if (chunk.name === "sw") {
                        return "firebase-messaging-sw.js"
                    }
                    return "[name].[hash].js"
                },
                format: "es"
            }
        }
    }
})
