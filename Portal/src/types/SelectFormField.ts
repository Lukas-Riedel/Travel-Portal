import type { BaseFormField } from "./BaseFormField.ts"
import type { SelectFormFieldOption } from "./SelectFormFieldOption.ts"

export interface SelectFormField<T> extends BaseFormField<T, "select"> {
    options: SelectFormFieldOption<T>[]
    multiple?: boolean
}