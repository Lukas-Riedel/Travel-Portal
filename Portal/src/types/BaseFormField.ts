export interface BaseFormField<T, V> {
  type: V
  required: boolean
  label?: string
  defaultValue?: T
  disabled?: boolean
}