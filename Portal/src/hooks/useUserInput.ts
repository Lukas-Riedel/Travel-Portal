import { useTranslation } from "react-i18next"
import { toast } from "sonner"
import type { UseUserInputResult } from "../types/UseUserInputResult.ts"

export const useUserInput = (): UseUserInputResult => {
    const { t } = useTranslation()

    const showConfirmToast = (message: string, success?: string, error?: string, onConfirmed?: () => Promise<void>) => {
        const id = toast(message, {
            action: {
                label: t("prompt.confirm"),
                onClick: () => {
                    toast.dismiss(id)
                    toast.promise(onConfirmed(), {
                        loading: t("prompt.loading"),
                        success: () => {
                            return success || t("prompt.confirmed")
                        },
                        error: e => {
                            console.error(e)
                            return error || t("prompt.failed")
                        },
                    })
                }
            },
            cancel: {
                label: t("prompt.reject"),
                onClick: () => { }
            }
        })
        return id
    }

    return {
        showConfirmToast
    }
}