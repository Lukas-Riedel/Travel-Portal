import type { BranchingToastBranch } from "./BranchingToastBranch.ts"
import type { FormField } from "./FormField.ts"

interface FormToastCallback<Args extends readonly unknown[]> {
    onSubmitted(...values: Args): Promise<unknown>
}

export interface UseUserInputResult {
    showConfirmToast: <R> (message: string, onConfirmed?: () => Promise<R>, success?: string, error?: string) => Promise<boolean>
    showFormToast: <F extends readonly FormField<unknown>[]> (message: string, fields: F, onSubmitted?: FormToastCallback<{ [K in keyof F]: F[K] extends FormField<infer T> ? T : never }>["onSubmitted"], success?: string, error?: string) => Promise<boolean>
    showInputToast: <T, R> (message: string, onSubmitted?: (value: T) => Promise<R>, success?: string, error?: string, defaultValue?: T) => Promise<boolean>
    showBranchingToast: (title: string, branches: Record<string, BranchingToastBranch>) => Promise<boolean>
}