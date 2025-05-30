import { LogIn } from "lucide-react"
import showLoginToast from "../components/LoginToast"
import { useAuth } from "../contexts/AuthContext"

export default function MainLayout({ children }) {
    const { isAdmin } = useAuth()

    return (
        <div className="max-w-6xl mt-8 mb-8 rounded-2xl mx-auto p-8 bg-white text-gray-900">
            {children}
            {!isAdmin() && (
                <div className="flex justify-center">
                    <button
                        className="rounded-full bg-white/80 backdrop-blur-sm text-black shadow-md hover:bg-gray-100 transition-colors px-3 py-2 text-sm font-medium inline-flex items-center space-x-2"
                        onClick={showLoginToast}>
                        <LogIn size={16} />
                    </button>
                </div>
            )}
        </div>
    )
}