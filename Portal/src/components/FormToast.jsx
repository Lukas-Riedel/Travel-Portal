import { toast } from "sonner"
import { useRef } from "react"

export default function showFormToast(title, fields, success, error, onSubmitted) {
    return toast.custom(
        t => {
            function Form() {
                const inputRefs = useRef([])

                const handleSubmit = async () => {
                    if (fields.filter(Boolean).some((field, index) => field.required && !inputRefs.current[index]?.value)) {
                        return
                    }

                    toast.dismiss(t.id)
                    const loadingId = toast.loading("Probíhá zpracování...")

                    try {
                        await onSubmitted(...fields.map((_, index) => inputRefs.current[index]?.value?.trim() || undefined))
                        toast.dismiss(loadingId)
                        toast.success(success)
                    }
                    catch (e) {
                        console.error(e)
                        toast.dismiss(loadingId)
                        toast.error(error)
                    }
                }

                return (
                    <div className="w-full flex justify-center">
                        <div className="bg-white rounded-lg shadow-md border p-4 w-80 space-y-3 text-sm">
                            {title && (
                                <div className="font-medium">
                                    {title}
                                </div>
                            )}
                            {fields.filter(Boolean).map((field, index) => (
                                <div key={index}>
                                    {field.label && (
                                        <label
                                            className="block mb-1 text-gray-600 text-sm">
                                            {field.label}{field.required ? "" : " (nepovinné)"}
                                        </label>
                                    )}
                                    {field.type === "select" ? (
                                        <select
                                            ref={element => (inputRefs.current[index] = element)}
                                            className="border rounded px-2 py-1 w-full text-sm"
                                            defaultValue={field.value ?? ""}
                                            disabled={field.disabled}>
                                            {field.options?.map((option, index) => (
                                                <option
                                                    key={index}
                                                    value={option.id}>
                                                    {option.name}
                                                </option>
                                            ))}
                                        </select>
                                    ) : (
                                        <input
                                            ref={element => (inputRefs.current[index] = element)}
                                            type={field.type || "text"}
                                            min={field.min}
                                            max={field.max}
                                            placeholder={field.placeholder}
                                            defaultValue={field.value}
                                            disabled={field.disabled}
                                            className="border rounded px-2 py-1 w-full text-sm"
                                            autoFocus={index === 0} />
                                    )}
                                </div>
                            ))}
                            <div className="flex justify-end gap-2">
                                <button
                                    className="px-3 py-1 rounded bg-gray-200"
                                    onClick={() => toast.dismiss(t.id)}>
                                    Zrušit
                                </button>
                                <button
                                    className="px-3 py-1 rounded bg-black text-white"
                                    onClick={handleSubmit}>
                                    Potvrdit
                                </button>
                            </div>
                        </div>
                    </div>
                )
            }
            return <Form />
        },
        { duration: Infinity })
}
