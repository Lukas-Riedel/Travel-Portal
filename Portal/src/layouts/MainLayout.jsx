import { LogIn, LogOut } from "lucide-react"
import { Link, useLocation } from "react-router-dom"
import showFormToast from "../components/FormToast"
import { useAuth } from "../contexts/AuthContext"
import showConfirmToast from "../components/ConfirmToast"

const navigationItems = [
    { label: "Výlety", to: "/trip", isProtected: false, allowedPrefixes: ["/", "/trip", "/year"] },
    { label: "Místa", to: "/place", isProtected: false, allowedPrefixes: ["/place", "/category"] },
    { label: "Lety", to: "/flight", isProtected: false, allowedPrefixes: ["/flight", "/airport", "/airline"] },
    { label: "Sledování času", to: "/tracker", isProtected: true, allowedPrefixes: ["/tracker"] }
]

export default function MainLayout({ children }) {
    const { login, logout, isAdmin } = useAuth()
    const location = useLocation()

    const handleLogin = () => {
        showFormToast(
            "Zadej přihlašovací údaje:",
            [
                { placeholder: "Uživatelské jméno", required: true },
                { placeholder: "Heslo", required: true, type: "password" }
            ],
            "Přihlášení proběhlo úspěšně",
            "Při přihlašování došlo k chybě",
            (username, password) => login({ username, password })
        )
    }

    const handleLogout = () => {
        showConfirmToast(
            "Opravdu se chceš odhlásit?",
            "Odhlášení proběhlo úspěšně",
            "Při odhlašování došlo k chybě",
            logout
        )
    }

    return (
        <div className="min-h-screen bg-gray-100 text-gray-900">
            <header className="bg-white shadow-md xl:sticky top-0 z-50">
                <div className="max-w-6xl mx-auto px-8 py-4 flex items-center justify-center md:justify-between">
                    <img
                        src="/icon.svg"
                        className="h-8 w-8 hidden md:block" />
                    <div className="flex items-center space-x-8">
                        <nav className="flex space-x-6 items-center text-center">
                            {navigationItems
                                .filter(({ to, isProtected }) => !isProtected || isAdmin || location.pathname === to)
                                .map(({ label, to, allowedPrefixes }) => {
                                    const isActive = allowedPrefixes.some(prefix => prefix === "/" ? location.pathname === prefix : location.pathname.startsWith(prefix))
                                    return (
                                        <Link
                                            key={to}
                                            to={to}
                                            className={`relative inline-block px-2 py-1 font-medium transition-colors duration-200
                                        ${isActive
                                                    ? "text-blue-700 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-full after:bg-blue-700"
                                                    : "text-gray-700 hover:text-blue-700 hover:after:absolute hover:after:left-0 hover:after:bottom-0 hover:after:h-0.5 hover:after:w-full hover:after:bg-blue-600"
                                                }`}>
                                            {label}
                                        </Link>

                                    )
                                })}
                        </nav>
                    </div>
                </div>
            </header>
            <main className="max-w-6xl mx-auto mt-8 mb-8 rounded-2xl px-2 py-8 md:px-8 md:py-8 bg-white">
                {children}
                <div className="flex justify-center mt-5">
                    <button
                        className="btn-large-gray"
                        onClick={isAdmin ? handleLogout : handleLogin}>
                        {isAdmin ? <LogOut size={16} /> : <LogIn size={16} />}
                    </button>
                </div>
            </main>
        </div>
    )
}
