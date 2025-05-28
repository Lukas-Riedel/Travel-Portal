import { toast } from "sonner"
import { useRef } from "react"

export default function showInputToast(promptText, placeholder, success, error, onSubmitted) {
    return toast.custom((t) => {
        function InputForm() {
            const inputRef = useRef(null)

            const handleSubmit = async () => {
                const value = inputRef.current?.value?.trim() || ""
                if (!value) {
                    return
                }
                toast.dismiss(t.id)

                try {
                    await onSubmitted(value)
                    toast.success(success)
                }
                catch (e) {
                    console.error(e)
                    toast.error(error)
                }
            }

            return (
                <div className="w-full flex justify-center">
                    <div className="bg-white rounded-lg shadow-md border p-4 w-80 space-y-3 text-sm">
                        <p className="font-medium">{promptText}</p>
                        <input
                            ref={inputRef}
                            type="text"
                            placeholder={placeholder}
                            className="border rounded px-2 py-1 w-full text-sm"
                            autoFocus />
                        <div className="flex justify-end gap-2">
                            <button
                                className="px-3 py-1 rounded bg-gray-200"
                                onClick={() => toast.dismiss(t.id)}>
                                Zrušit
                            </button>
                            <button
                                className="px-3 py-1 rounded bg-black text-white"
                                onClick={handleSubmit}>
                                OK
                            </button>
                        </div>
                    </div>
                </div>
            )
        }

        return <InputForm />
    })
}
