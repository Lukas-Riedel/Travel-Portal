import { defineConfig, mergeConfig } from "vite"
import checker from "vite-plugin-checker"
import baseConfig from "./vite.config.base.ts"

export default defineConfig(
    mergeConfig(baseConfig, {
        plugins: [
            checker({
                typescript: true,
                overlay: true
            }),
        ],
        server: {
            proxy: {
                "/api": {
                    target: "https://lriedel.cz",
                    changeOrigin: true,
                    secure: false,
                },
                "/iam": {
                    target: "https://lriedel.cz",
                    changeOrigin: true,
                    secure: false,
                },
            },
        }
    })
)