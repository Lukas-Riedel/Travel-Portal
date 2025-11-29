import type { FormField } from "./FormField.ts"

export interface UseUserInputResult {
    showConfirmToast: (message: string, onConfirmed?: () => Promise<void>, success?: string, error?: string) => Promise<boolean>
    showFormToast: <F extends readonly FormField<any>[]> (message: string, fields: F, onSubmitted?: (...values: { [K in keyof F]: F[K] extends FormField<infer T> ? T : never }) => Promise<void>, success?: string, error?: string) => Promise<boolean>
    showInputToast: <T> (message: string, onSubmitted?: (value: T) => Promise<void>, success?: string, error?: string, defaultValue?: T) => Promise<boolean>
}