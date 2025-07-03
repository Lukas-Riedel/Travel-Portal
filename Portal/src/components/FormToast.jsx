import { toast } from "sonner"
import { useRef } from "react"

export default function showFormToast(title, fields, success, error, onSubmitted) {
    return toast.custom(t => {
        function Form() {
            const inputRefs = useRef([])

            const handleSubmit = async () => {
                if (fields.some((field, idx) => field.required && !inputRefs.current[idx]?.value)) {
                    return
                }

                toast.dismiss(t.id)
                const loadingId = toast.loading("Probíhá zpracování...")

                try {
                    await onSubmitted(...fields.map((_, idx) => inputRefs.current[idx]?.value?.trim() || undefined))
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
                        {title && <div className="font-medium">{title}</div>}
                        {fields.map((field, idx) => (
                            field.type === "select" ? (
                                <select
                                    key={idx}
                                    ref={element => (inputRefs.current[idx] = element)}
                                    className="border rounded px-2 py-1 w-full text-sm"
                                    defaultValue={field.value ?? ""}>
                                    {field.options?.map((option, idx) => (
                                        <option
                                            key={idx}
                                            value={option.id}>
                                            {option.name}
                                        </option>
                                    ))}
                                </select>
                            ) : (
                                <input
                                    key={idx}
                                    ref={element => (inputRefs.current[idx] = element)}
                                    type={field.type || "text"}
                                    min={field.min}
                                    placeholder={`${field.placeholder ?? ""} ${field.required ? "" : "(nepovinné)"}`}
                                    defaultValue={field.value}
                                    className="border rounded px-2 py-1 w-full text-sm"
                                    autoFocus={idx === 0} />
                            )
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
    })
}
