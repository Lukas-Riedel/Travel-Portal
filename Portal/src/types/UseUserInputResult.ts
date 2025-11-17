export interface UseUserInputResult {
    showConfirmToast: (message: string, success?: string, error?: string, onConfirmed?: () => Promise<void>) => string | number
}