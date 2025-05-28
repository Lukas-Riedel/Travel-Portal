import { toast } from "sonner";

export default function showConfirmToast(question, success, error, onConfirmed) {
    return toast(question, {
        action: {
            label: "Ano",
            onClick: async () => {
                try {
                    await onConfirmed();
                    toast.success(success);
                }
                catch (e) {
                    console.error(e);
                    toast.error(error);
                }
            }
        },
        cancel: {
            label: "Ne"
        }
    })
}