import type { InputFormField } from "./InputFormField.ts"
import type { SelectFormField } from "./SelectFormField.ts"

export type FormField<T> = SelectFormField<T> | InputFormField<T>