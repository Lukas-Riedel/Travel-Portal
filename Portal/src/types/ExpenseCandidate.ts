import type { ExpenseType } from "./CoreSwaggerTypes"

export interface ExpenseCandidate {
    type?: ExpenseType
    description?: string
    value?: number
    currency?: string
}