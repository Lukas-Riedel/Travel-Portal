import { LogIn } from "lucide-react"
import showLoginToast from "../components/LoginToast"
import { useAuth } from "../contexts/AuthContext"

export default function MainLayout({ children }) {
    const { isAdmin } = useAuth()

    return (
        <div className="max-w-6xl mt-8 mb-8 rounded-2xl mx-auto p-8 bg-white text-gray-900">
            {children}
            {!isAdmin() && (
                <div className="flex justify-center mt-5">
                    <button
                        className="btn-large-gray"
                        onClick={showLoginToast}>
                        <LogIn size={16} />
                    </button>
                </div>
            )}
        </div>
    )
}