import type { Credentials } from "../types/Credentials.ts"

export const GUEST_CREDENTIALS: Credentials = {
    username: (window.env?.VITE_DEFAULT_USERNAME || import.meta.env.VITE_DEFAULT_USERNAME),
    password: (window.env?.VITE_DEFAULT_PASSWORD || import.meta.env.VITE_DEFAULT_PASSWORD)
}