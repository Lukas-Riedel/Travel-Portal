import { toast } from "sonner"
import { useRef } from "react"

export default function showBranchingToast(title, branches) {
    return toast.custom(
        t => {
            function Form() {
                const selectRef = useRef(null)

                const handleSubmit = () => {
                    toast.dismiss(t.id)
                    branches[selectRef.current.value].handle()
                }

                return (
                    <div className="w-full flex justify-center">
                        <div className="bg-white rounded-lg shadow-md border p-4 w-80 space-y-3 text-sm">
                            {title && (
                                <div className="font-medium">
                                    {title}
                                </div>
                            )}
                            <div>
                                <select
                                    ref={element => (selectRef.current = element)}
                                    className="border rounded px-2 py-1 w-full text-sm">
                                    {Object.keys(branches).map(branch => (
                                        <option
                                            key={branch}
                                            value={branch}>
                                            {branches[branch].name}
                                        </option>
                                    ))}
                                </select>
                            </div>
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
