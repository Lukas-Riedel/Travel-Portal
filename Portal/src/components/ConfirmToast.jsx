import { toast } from "sonner"

export default function showConfirmToast(question, success, error, onConfirmed) {
    const id = toast(question, {
        action: {
            label: "Ano",
            onClick: async () => {
                toast.dismiss(id)
                const loadingId = toast.loading("Probíhá zpracování...")

                try {
                    await onConfirmed()
                    toast.dismiss(loadingId)
                    toast.success(success)
                }
                catch (e) {
                    console.error(e)
                    toast.dismiss(loadingId)
                    toast.error(error)
                }
            }
        },
        cancel: {
            label: "Ne"
        }
    })

    return id
}
