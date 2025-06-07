import { LogIn, LogOut } from "lucide-react"
import showLoginToast from "../components/LoginToast"
import { useAuth } from "../contexts/AuthContext"
import showConfirmToast from "../components/ConfirmToast"

export default function MainLayout({ children }) {
    const { logout, isAdmin } = useAuth()

    const handleLogout = () => {
        showConfirmToast("Opravdu se chceš odhlásit?",
            "Odhlášení proběhlo úspěšně",
            "Při odhlašování došlo k chybě",
            logout
        )
    }

    return (
        <div className="max-w-6xl mt-8 mb-8 rounded-2xl mx-auto p-8 bg-white text-gray-900">
            {children}
            <div className="flex justify-center mt-5">
                <button
                    className="btn-large-gray"
                    onClick={isAdmin() ? handleLogout : showLoginToast}>
                    {isAdmin() ? <LogOut size={16} /> : <LogIn size={16} />}
                </button>
            </div>
        </div>
    )
}