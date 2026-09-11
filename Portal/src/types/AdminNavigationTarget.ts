import type { AdminMenuTabName } from "./AdminMenuTabName.ts"

export interface AdminNavigationTarget {
    tab: AdminMenuTabName
    key?: string
}
