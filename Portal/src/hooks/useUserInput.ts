import { useTranslation } from "react-i18next"
import { useRef } from "react"
import { toast } from "sonner"
import type { UseUserInputResult } from "../types/UseUserInputResult.ts"
import showFormToast from "../components/FormToast.jsx"

export const useUserInput = (): UseUserInputResult => {
    const { t } = useTranslation()

    const showConfirmToast = (message: string, onConfirmed?: () => Promise<void>, success?: string, error?: string): Promise<boolean> =>
        new Promise((resolve, reject) => {
            const id = toast(message, {
                action: {
                    label: t("prompt.confirm"),
                    onClick: () => {
                        toast.dismiss(id)
                        if (onConfirmed) {
                            toast.promise(onConfirmed(), {
                                loading: t("prompt.loading"),
                                success: () => {
                                    resolve(true)
                                    return success || t("prompt.confirmed")
                                },
                                error: e => {
                                    reject(e)
                                    return error || t("prompt.failed")
                                },
                            })
                        }
                        else {
                            resolve(true)
                        }
                    }
                },
                cancel: {
                    label: t("prompt.reject"),
                    onClick: () => {
                        resolve(false)
                    }
                }
            })
        })

    return {
        showConfirmToast,
        showInputToast: <T>(message: string, onSubmitted?: (value: T) => Promise<void>, success?: string, error?: string, defaultValue?: T) =>
            showFormToast(message, [{ value: defaultValue, required: true }], success, error, onSubmitted)
    }
}