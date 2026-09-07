import js from "@eslint/js"
import globals from "globals"
import reactHooks from "eslint-plugin-react-hooks"
import reactRefresh from "eslint-plugin-react-refresh"
import tseslint from "typescript-eslint"

export default tseslint.config(
    { ignores: ["dist"] },
    {
        files: ["src/firebase-messaging-sw.js"],
        languageOptions: {
            ecmaVersion: 2020,
            globals: {
                ...globals.browser,
                ...globals.serviceworker,
                __VITE_FIREBASE_API_KEY__: "readonly",
                __VITE_FIREBASE_AUTH_DOMAIN__: "readonly",
                __VITE_FIREBASE_PROJECT_ID__: "readonly",
                __VITE_FIREBASE_STORAGE_BUCKET__: "readonly",
                __VITE_FIREBASE_MESSAGING_SENDER_ID__: "readonly",
                __VITE_FIREBASE_APP_ID__: "readonly",
                __VITE_FIREBASE_MEASUREMENT_ID__: "readonly",
                __VITE_PORTAL_BASE_URL__: "readonly",
            },
            sourceType: "module",
        },
        rules: {
            ...js.configs.recommended.rules,
        },
    },
    {
        files: ["**/*.{ts,tsx}"],
        extends: [
            ...tseslint.configs.recommended,
        ],
        languageOptions: {
            globals: globals.browser,
        },
        plugins: {
            "react-hooks": reactHooks,
            "react-refresh": reactRefresh,
        },
        rules: {
            ...reactHooks.configs.recommended.rules,
            "react-refresh/only-export-components": [
                "warn",
                { allowConstantExport: true },
            ],
            "@typescript-eslint/no-unused-vars": ["error", {
                varsIgnorePattern: "^_",
                argsIgnorePattern: "^_",
                caughtErrorsIgnorePattern: "^_",
                ignoreRestSiblings: true,
            }],
        },
    },
)
