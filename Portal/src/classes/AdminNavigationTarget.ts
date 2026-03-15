import type { AdminMenuTabName } from "../types/AdminMenuTabName.ts"

export class AdminNavigationTarget {
    tab: AdminMenuTabName
    key?: string

    constructor(tab: AdminMenuTabName, key?: string) {
        this.tab = tab
        this.key = key
    }

    getURLSearchParams() {
        const params: Record<string, string> = {
            tab: this.tab
        }

        if (this.key) {
            params.key = this.key
        }

        return new URLSearchParams(params)
    }
}