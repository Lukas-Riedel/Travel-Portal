import type { Navigable } from "./Navigable.ts"

export type UseAppNavigateResult = (to: Navigable) => void | Promise<void>