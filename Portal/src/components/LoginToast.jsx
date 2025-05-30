import { toast } from "sonner"
import { useRef } from "react"
import { useAuth } from "../contexts/AuthContext"

export default function showLoginToast() {
    return toast.custom((t) => {
        function LoginForm() {
            const usernameRef = useRef(null)
            const passwordRef = useRef(null)
            const { login } = useAuth()

            const handleSubmitted = async () => {
                const username = usernameRef.current?.value?.trim() || ""
                const password = passwordRef.current?.value || ""

                if (!username || !password) {
                    return
                }

                toast.dismiss(t.id)

                try {
                    await login({ username, password })
                    toast.success("Přihlášení proběhlo úspěšně")
                } catch (e) {
                    console.error(e)
                    toast.error("Při přihlašování došlo k chybě")
                }
            }

            return (
                <div className="w-full flex justify-center">
                    <div className="bg-white rounded-lg shadow-md border p-4 w-80 space-y-3 text-sm">
                        <input
                            ref={usernameRef}
                            type="text"
                            placeholder="Uživatelské jméno"
                            className="border rounded px-2 py-1 w-full text-sm"
                            autoFocus />
                        <input
                            ref={passwordRef}
                            type="password"
                            placeholder="Heslo"
                            className="border rounded px-2 py-1 w-full text-sm" />
                        <div className="flex justify-end gap-2">
                            <button
                                className="px-3 py-1 rounded bg-gray-200"
                                onClick={() => toast.dismiss(t.id)}>
                                Zrušit
                            </button>
                            <button
                                className="px-3 py-1 rounded bg-black text-white"
                                onClick={handleSubmitted}>
                                Přihlásit
                            </button>
                        </div>
                    </div>
                </div>
            )
        }

        return <LoginForm />
    })
}
