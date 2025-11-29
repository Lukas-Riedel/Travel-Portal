import type { HTMLInputTypeAttribute } from "react"
import type { BaseFormField } from "./BaseFormField.ts"

export interface InputFormField<T> extends BaseFormField<T, HTMLInputTypeAttribute> {
    placeholder?: T
    min?: number
    max?: number
}