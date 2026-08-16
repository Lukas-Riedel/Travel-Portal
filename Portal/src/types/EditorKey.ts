import type { Navigable } from "./Navigable"

export interface EditorKey {
    name: string
    label: string
    target?: Navigable
}