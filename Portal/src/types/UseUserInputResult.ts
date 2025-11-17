export interface UseUserInputResult {
    showConfirmToast: (message: string, onConfirmed?: () => Promise<void>, success?: string, error?: string) => Promise<boolean>
    showInputToast: <T> (message: string, onSubmitted?: (value: T) => Promise<void>, success?: string, error?: string, defaultValue?: T) => Promise<void>
}