import { useTranslation } from "react-i18next"
import { toast } from "sonner"
import type { UseUserInputResult } from "../types/UseUserInputResult.ts"
import type { FormField } from "../types/FormField.ts"
import { useCallback, useRef } from "react"
import type { SelectFormField } from "../types/SelectFormField.ts"
import type { BranchingToastBranch } from "../types/BranchingToastBranch.ts"

export const useUserInput = (): UseUserInputResult => {
    const { t } = useTranslation()

    const showConfirmToast = useCallback(<R extends any>(message: string, onConfirmed?: () => Promise<R>, success?: string, error?: string): Promise<boolean> =>
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
        }), [t])

    const showFormToast = useCallback(<F extends readonly FormField<any>[], R extends any>(message: string, fields: F, onSubmitted?: (...values: { [K in keyof F]: F[K] extends FormField<infer T> ? T : never }) => Promise<R>, success?: string, error?: string): Promise<boolean> =>
        new Promise((resolve, reject) => {
            toast.custom(
                id => {
                    // TODO: Extract to a separate component in order to follow React best practices.
                    function Form() {
                        const inputRefs = useRef<(HTMLInputElement | HTMLSelectElement | null)[]>([])

                        const handleSubmit = async () => {
                            if (fields.filter(Boolean).some((field, index) => field.required && !inputRefs.current[index]?.value)) {
                                return
                            }

                            const args = fields.map((_, index) => {
                                const inputElement = inputRefs.current[index]
                                if (!inputElement) {
                                    return undefined
                                }

                                if (inputElement instanceof HTMLSelectElement) {
                                    if (inputElement.multiple) {
                                        const values = Array.from(inputElement.selectedOptions, optionElement => optionElement.value.trim())
                                        return values.length ? values : undefined
                                    }
                                    else {
                                        return inputElement.value.trim() || undefined
                                    }
                                }

                                if (inputElement instanceof HTMLInputElement) {
                                    return inputElement.value.trim() || undefined
                                }

                                return undefined
                            }) as { [K in keyof F]: F[K] extends FormField<infer T> ? T : never }

                            toast.dismiss(id)

                            if (onSubmitted) {
                                toast.promise(onSubmitted(...args), {
                                    loading: t("prompt.loading"),
                                    success: () => {
                                        resolve(true)
                                        return success || t("prompt.confirmed")
                                    },
                                    error: e => {
                                        reject(e)
                                        return error || t("prompt.failed")
                                    }
                                })
                            }
                            else {
                                resolve(true)
                            }
                        }

                        const handleCancel = () => {
                            toast.dismiss(id)
                            resolve(false)
                        }

                        const isSelectFormField = <T extends any>(field: FormField<T>): field is SelectFormField<T> => field.type === "select"

                        return (
                            <div className="w-full flex justify-center">
                                <div className="bg-white rounded-lg shadow-md border p-4 w-80 space-y-3 text-sm">
                                    {message && (
                                        <div className="font-medium">
                                            {message}
                                        </div>
                                    )}
                                    {fields.filter(Boolean).map((field, index) => {
                                        const roundedDefaultValue = (() => {
                                            if (!isSelectFormField(field)) {
                                                return undefined
                                            }

                                            const dv = field.defaultValue
                                            if (dv == null) {
                                                return ""
                                            }

                                            const options = field.options.map(o => ({
                                                id: String(o.id),
                                                num: Number(o.id),
                                            }))

                                            if (typeof dv === "number") {
                                                const numeric = options.filter(o => !Number.isNaN(o.num))

                                                if (numeric.length > 0) {
                                                    const closest = numeric.reduce((prev, curr) => {
                                                        const prevDiff = Math.abs(prev.num - dv)
                                                        const currDiff = Math.abs(curr.num - dv)
                                                        return currDiff < prevDiff ? curr : prev
                                                    })

                                                    return closest.id
                                                }
                                            }

                                            const exact = options.find(o => o.id === String(dv))
                                            if (exact) {
                                                return exact.id
                                            }

                                            return ""
                                        })()

                                        return (
                                            <div key={index}>
                                                {field.label && (
                                                    <label className="block mb-1 text-gray-600 text-sm">
                                                        {field.label}
                                                        {field.required && (
                                                            <span className="text-red-600">
                                                                {"*"}
                                                            </span>
                                                        )}
                                                    </label>
                                                )}
                                                {isSelectFormField(field) ? (
                                                    <select
                                                        ref={element => {
                                                            if (element) {
                                                                inputRefs.current[index] = element
                                                            }
                                                        }}
                                                        className="border rounded px-2 py-1 w-full text-sm"
                                                        defaultValue={roundedDefaultValue}
                                                        multiple={field.multiple}
                                                        disabled={field.disabled}>
                                                        {!field.required && (
                                                            <option key="empty" value="">
                                                                {""}
                                                            </option>
                                                        )}
                                                        {field.options.map(option => (
                                                            <option
                                                                key={option.id}
                                                                value={option.id}>
                                                                {option.name}
                                                            </option>
                                                        ))}
                                                    </select>
                                                ) : (
                                                    <input
                                                        ref={element => {
                                                            if (element) {
                                                                inputRefs.current[index] = element
                                                            }
                                                        }}
                                                        className="border rounded px-2 py-1 w-full text-sm"
                                                        type={field.type ?? "text" /** TODO: Remove the default type after the transition to TypeScript is done. */}
                                                        min={field.min}
                                                        max={field.max}
                                                        placeholder={field.placeholder}
                                                        defaultValue={field.defaultValue}
                                                        disabled={field.disabled}
                                                        autoFocus={index === 0} />
                                                )}
                                            </div>
                                        )
                                    })}
                                    <div className="flex justify-end gap-2">
                                        <button
                                            className="px-3 py-1 rounded bg-gray-200"
                                            onClick={handleCancel}>
                                            {t("prompt.reject")}
                                        </button>
                                        <button
                                            className="px-3 py-1 rounded bg-black text-white"
                                            onClick={handleSubmit}>
                                            {t("prompt.confirm")}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        )
                    }
                    return <Form />
                },
                {
                    duration: Infinity
                }
            )
        }), [t])

    const showInputToast = <T extends any, R extends any>(message: string, onSubmitted?: (value: T) => Promise<R>, success?: string, error?: string, defaultValue?: T): Promise<boolean> =>
        showFormToast(message, [{ type: "text", required: true, defaultValue }], onSubmitted, success, error)

    const showBranchingToast = useCallback((title: string, branches: Record<string, BranchingToastBranch>) => {
        const options = Object.entries(branches).map(([id, branch]) => ({ id, name: branch.name }));

        // TODO: Do not show success or error messages when moving to the next branch.
        return showFormToast(title, [{ type: "select", required: true, options: options, defaultValue: options[0]?.id }],
            async selectedId => {
                const branch = branches[selectedId];
                if (branch) {
                    branch.handle()
                    return true
                }
            })
    }, [showFormToast])

    return {
        showConfirmToast,
        showFormToast,
        showInputToast,
        showBranchingToast
    }
}