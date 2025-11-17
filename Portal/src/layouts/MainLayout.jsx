import { LogIn, LogOut } from "lucide-react"
import { Link, useLocation } from "react-router-dom"
import showFormToast from "../components/FormToast"
import { useAuth } from "../contexts/AuthContext"
import { useState } from "react"
import { useTranslation } from "react-i18next"
import { useUserInput } from "../hooks/useUserInput.ts"

export default function MainLayout({ children }) {
    const { login, logout, isAdmin } = useAuth()
    const location = useLocation()
    const [isMenuOpen, setIsMenuOpen] = useState(false)
    const { t } = useTranslation()
    const { showConfirmToast } = useUserInput()

    const navigationItems = [
        { label: t("menu.feed"), to: "/feed", isProtected: false, allowedPrefixes: ["/feed"] },
        { label: t("menu.trips"), to: "/trip", isProtected: false, allowedPrefixes: ["/trip", "/year"] },
        { label: t("menu.places"), to: "/place", isProtected: false, allowedPrefixes: ["/place", "/category"] },
        { label: t("menu.flights"), to: "/flight", isProtected: false, allowedPrefixes: ["/flight", "/airport", "/airline"] },
        { label: t("menu.statistics"), to: "/statistics", isProtected: false, allowedPrefixes: ["/statistics"] },
        { label: t("menu.plan"), to: "/plan", isProtected: true, allowedPrefixes: ["/plan"] },
        { label: t("menu.tracker"), to: "/tracker", isProtected: true, allowedPrefixes: ["/tracker"] },
        { label: t("menu.admin"), to: "/admin", isProtected: true, allowedPrefixes: ["/admin"] }
    ]

    const handleLogin = () => {
        showFormToast(
            "Zadej přihlašovací údaje:",
            [
                { label: "Uživatelské jméno", required: true },
                { label: "Heslo", required: true, type: "password" }
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

    const filteredItems = navigationItems.filter(({ to, isProtected }) => !isProtected || isAdmin || location.pathname.startsWith(to))

    return (
        <div className="min-h-screen bg-gray-100 text-gray-900">
            <header className="bg-white shadow-md xl:sticky top-0 z-50">
                <div className="max-w-6xl mx-auto px-8 py-4 flex items-center justify-center md:justify-between">
                    <Link
                        to={"/"}>
                        <img
                            src="/icon.svg"
                            className="h-8 w-8 hidden md:block" />
                    </Link>
                    <button
                        className="md:hidden p-2 rounded hover:bg-gray-200"
                        onClick={() => setIsMenuOpen(!isMenuOpen)}>
                        {isMenuOpen ? (
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        ) : (
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        )}
                    </button>
                    <nav className="hidden md:flex space-x-6 items-center text-center">
                        {filteredItems.map(({ label, to, allowedPrefixes }) => {
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
                {isMenuOpen && (
                    <nav className="md:hidden bg-white border-t border-gray-200">
                        <ul className="flex flex-col p-4 space-y-2 items-center text-center">
                            {filteredItems.map(({ label, to }) => (
                                <li
                                    key={to}
                                    className="w-full">
                                    <Link
                                        to={to}
                                        className="block w-full px-3 py-2 rounded hover:bg-gray-100"
                                        onClick={() => setIsMenuOpen(false)}>
                                        {label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </nav>
                )}
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
