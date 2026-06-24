export interface BaseFormField<T, V> {
  type: V
  required: boolean
  label?: string
  defaultValue?: T
  disabled?: boolean
  onChange?: (value: any, refs: (HTMLInputElement | HTMLSelectElement | null)[]) => void
}