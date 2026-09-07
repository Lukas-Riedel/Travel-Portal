export interface BaseFormField<T, V> {
  type: V
  required: boolean
  label?: string
  defaultValue?: T
  disabled?: boolean
  onChange?: (value: unknown, refs: (HTMLInputElement | HTMLSelectElement | null)[]) => void
}