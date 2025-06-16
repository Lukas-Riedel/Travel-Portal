import { LogIn, LogOut } from "lucide-react"
import showFormToast from "../components/FormToast"
import { useAuth } from "../contexts/AuthContext"
import showConfirmToast from "../components/ConfirmToast"

export default function MainLayout({ children }) {
    const { login, logout, isAdmin } = useAuth()

    const handleLogin = () => {
        showFormToast(
            "Zadej přihlašovací údaje:",
            [
                { placeholder: "Uživatelské jméno" , required: true},
                { placeholder: "Heslo", required: true, type: "password" }
            ],
            "Přihlášení proběhlo úspěšně",
            "Při přihlašování došlo k chybě",
            (username, password) => login({ username, password })
        )
    }

    const handleLogout = () => {
        showConfirmToast("Opravdu se chceš odhlásit?",
            "Odhlášení proběhlo úspěšně",
            "Při odhlašování došlo k chybě",
            logout
        )
    }

    return (
        <div className="max-w-6xl mt-8 mb-8 rounded-2xl mx-auto px-2 py-8 md:px-8 md:py-8 bg-white text-gray-900">
            {children}
            <div className="flex justify-center mt-5">
                <button
                    className="btn-large-gray"
                    onClick={isAdmin ? handleLogout : handleLogin}>
                    {isAdmin ? <LogOut size={16} /> : <LogIn size={16} />}
                </button>
            </div>
        </div>
    )
}