import { Backpack, Calendar, CircleQuestionMark, Earth, House, Landmark, LogIn, LogOut, Map as MapIcon, MapPin, Plane, PlaneIcon, Search, Tag, TowerControl, TreePalm, Waves, XIcon } from "lucide-react"
import { Link, useLocation } from "react-router-dom"
import { useAuth } from "../contexts/AuthContext"
import { useEffect, useMemo, useState } from "react"
import { useTranslation } from "react-i18next"
import { UserRole } from "../types/CoreSwaggerTypes.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { search } from "../clients/coreClient.ts"
import { getPrettyName } from "../utils/helpers.js"
import { useCategories } from "../hooks/useCategories.ts"

const searchableEntityTypeSelectors = {
    "category": entity => categoryCategories[entity.category] ?? "Region",
    "place": _ => "Místo",
    "airport": _ => "Letiště",
    "airline": _ => "Aerolinka",
    "label": _ => "Štítek",
    "trip": _ => "Výlet",
    "year": _ => "Rok"
}

const searchableEntityIconSelectors = {
    "category": entity => categoryCategoryIcons[entity.category] ?? Map,
    "place": _ => MapPin,
    "airport": _ => TowerControl,
    "airline": _ => PlaneIcon,
    "label": _ => Tag,
    "trip": _ => Backpack,
    "year": _ => Calendar
}

// TODO: This is duplicated in CategoryPage.
const categoryCategories = {
    continent: "Kontinent",
    country: "Stát",
    administrative: "Administrativní oblast",
    ocean: "Oceán",
    sea: "Moře",
    bay: "Záliv",
    island: "Ostrov",
    region: "Geografický region"
}

const categoryCategoryIcons = {
    continent: Earth,
    country: MapIcon,
    administrative: Landmark,
    ocean: Waves,
    sea: Waves,
    bay: Waves,
    island: TreePalm,
    region: MapIcon
}

const searchableEntityNameSelectors = {
    "category": entity => getPrettyName(entity.name),
    "place": entity => getPrettyName(entity.name),
    "airport": entity => entity.longName,
    "airline": entity => entity.name,
    "label": entity => entity.name,
    "trip": entity => entity.name + " " + entity.year,
    "year": entity => entity.id
}

const DEFAULT_SEARCH_RESULTS_COUNT = 10

export default function MainLayout({ children }) {
    const { isLoggedIn, username, login, logout, hasRole } = useAuth()
    const location = useLocation()
    const [isMenuOpen, setIsMenuOpen] = useState(false)
    const { t } = useTranslation()
    const { showLogoutToast, showLoginToast } = usePredefinedUserInput()
    const [isSearchOpen, setIsSearchOpen] = useState(false)
    const [searchResults, setSearchResults] = useState(null)
    const [searchedText, setSearchedText] = useState(null)
    const countryCategories = useCategories({ categories: ["country"] })

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])

    const navigationItems = [
        { label: t("menu.search"), onClick: () => setIsSearchOpen(true) },
        { label: t("menu.feed"), to: "/feed", requiredRole: UserRole.PlaceRead, allowedPrefixes: ["/feed"] },
        { label: t("menu.trips"), to: "/trip", requiredRole: UserRole.TripRead, allowedPrefixes: ["/trip", "/year"] },
        { label: t("menu.places"), to: "/place", requiredRole: UserRole.PlaceRead, allowedPrefixes: ["/place", "/category"] },
        { label: t("menu.flights"), to: "/flight", requiredRole: UserRole.TripFlightRead, allowedPrefixes: ["/flight", "/airport", "/airline"] },
        { label: t("menu.statistics"), to: "/statistics", requiredRole: UserRole.StatisticsRead, allowedPrefixes: ["/statistics"] },
        // TODO: Find a better required role.
        { label: t("menu.plan"), to: "/plan", requiredRole: UserRole.PortalFutureRead, allowedPrefixes: ["/plan"] },
        { label: t("menu.tracker"), to: "/tracker", requiredRole: UserRole.TrackerEdit, allowedPrefixes: ["/tracker"] },
        // TODO: Find a better requried role.
        { label: t("menu.admin"), to: "/admin", requiredRole: UserRole.ConfigurationEdit, allowedPrefixes: ["/admin"] },
    ]

    const handleLogin = () => {
        requestPermissions()
        showLoginToast((username, password) => login({ username, password }))
    }

    const handleLogout = () => {
        requestPermissions()
        showLogoutToast(logout)
    }

    useEffect(() => {
        const controller = new AbortController()

        const fetchSearch = async () => {
            if (searchedText?.length > 2) {
                try {
                    const delay = setTimeout(async () => {
                        const results = await search(searchedText, {
                            limit: DEFAULT_SEARCH_RESULTS_COUNT,
                            include: ["category", "place", "airport", "airline", "label", "trip", "year"]
                        }, {
                            signal: controller.signal
                        });
                        setSearchResults(results);
                    }, 200);

                    return () => clearTimeout(delay);
                }
                catch (e) {
                    // Do nothing.
                }
            }
            else {
                setSearchResults(null);
            }
        }

        fetchSearch()

        return () => {
            controller.abort()
        }
    }, [searchedText])

    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === "Escape") {
                setIsSearchOpen(false)
            }
            if ((e.metaKey || e.ctrlKey) && e.key === "k") {
                e.preventDefault()
                setIsSearchOpen(true)
            }
        };
        window.addEventListener("keydown", handleKeyDown)
        return () => window.removeEventListener("keydown", handleKeyDown)
    }, [])

    const filteredItems = navigationItems.filter(({ to, requiredRole }) => !requiredRole || hasRole(requiredRole) || location.pathname.startsWith(to))

    const renderIcon = (type, entity) => {
        if (type === "place" || type === "airport") {
            const country = countryCategoriesMap.get(entity.country)

            if (country) {
                return (
                    <div className="w-5 h-5 flex items-center justify-center overflow-hidden rounded-sm flex-shrink-0">
                        <img
                            className="w-full h-full object-cover shadow-sm"
                            src={`/img/flags/${country?.metadata?.unicode}.svg`}
                            alt={country?.name}
                        />
                    </div>
                )
            }
        }

        if (type === "category" && entity.category === "country") {
            const country = countryCategoriesMap.get(entity.name)

            if (country) {
                return (
                    <div className="w-5 h-5 flex items-center justify-center overflow-hidden rounded-sm flex-shrink-0">
                        <img
                            className="w-full h-full object-cover shadow-sm"
                            src={`/img/flags/${country?.metadata?.unicode}.svg`}
                            alt={country?.name}
                        />
                    </div>
                )
            }
        }

        const Icon = searchableEntityIconSelectors[type]?.(entity) ?? CircleQuestionMark
        return (
            <span className="text-gray-400">
                <Icon size={20} />
            </span>
        )
    }

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
                        {filteredItems.map(({ label, to, onClick, allowedPrefixes }) => {
                            const isActive = allowedPrefixes?.some(prefix => prefix === "/" ? location.pathname === prefix : location.pathname.startsWith(prefix))
                            return to ? (
                                <Link
                                    key={label}
                                    to={to}
                                    onClick={onClick}
                                    className={`relative inline-block px-2 py-1 font-medium transition-colors duration-200
                                        ${isActive
                                            ? "text-blue-700 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-full after:bg-blue-700"
                                            : "text-gray-700 hover:text-blue-700 hover:after:absolute hover:after:left-0 hover:after:bottom-0 hover:after:h-0.5 hover:after:w-full hover:after:bg-blue-600"
                                        }`}>
                                    {label}
                                </Link>
                            ) : (
                                <button
                                    key={label}
                                    onClick={onClick}
                                    className={`relative inline-block px-2 py-1 font-medium transition-colors duration-200 cursor-pointer
                                        ${isActive
                                            ? "text-blue-700 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-full after:bg-blue-700"
                                            : "text-gray-700 hover:text-blue-700 hover:after:absolute hover:after:left-0 hover:after:bottom-0 hover:after:h-0.5 hover:after:w-full hover:after:bg-blue-600"
                                        }`}>
                                    {label}
                                </button>
                            )
                        })}
                    </nav>
                </div>
                {isMenuOpen && (
                    <nav className="md:hidden bg-white border-t border-gray-200">
                        <ul className="flex flex-col p-4 space-y-2 items-center text-center">
                            {filteredItems.map(({ label, to, onClick }) => (
                                <li
                                    key={label}
                                    className="w-full">
                                    {to ? (
                                        <Link
                                            to={to}
                                            className="block w-full px-3 py-2 rounded hover:bg-gray-100"
                                            onClick={() => {
                                                if (onClick) {
                                                    onClick()
                                                }
                                                setIsMenuOpen(false)
                                            }}>
                                            {label}
                                        </Link>
                                    ) : (
                                        <button
                                            className="block w-full px-3 py-2 rounded hover:bg-gray-100"
                                            onClick={() => {
                                                if (onClick) {
                                                    onClick()
                                                }
                                                setIsMenuOpen(false)
                                            }}>
                                            {label}
                                        </button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </nav>
                )}
            </header>
            {isSearchOpen && (
                <div
                    className="fixed inset-0 z-[60] flex items-start justify-center pt-[10vh] bg-gray-900/50 backdrop-blur-sm"
                    onClick={() => setIsSearchOpen(false)}>
                    <div
                        className="w-full max-w-xl bg-white rounded-xl shadow-2xl overflow-hidden"
                        onClick={e => e.stopPropagation()}>
                        <div className="flex items-center p-4 border-b">
                            <Search className="text-gray-400 mr-3" />
                            <input
                                autoFocus
                                className="w-full outline-none text-lg"
                                placeholder="Zadej hledaný výraz"
                                value={searchedText}
                                onChange={e => setSearchedText(e.target.value)} />
                            <button onClick={() => setIsSearchOpen(false)} className="text-xs p-1 bg-gray-100 rounded">
                                <XIcon size={16} />
                            </button>
                        </div>
                        <div className="max-h-[60vh] overflow-y-auto p-2">
                            {searchResults && (
                                searchResults.length ?
                                    searchResults.map(res => (
                                        <Link
                                            key={res.entity.id}
                                            className="p-3 hover:bg-blue-50 rounded-lg cursor-pointer flex justify-between"
                                            to={`/${res.type}/${res.entity.id}`}
                                            onClick={() => setIsSearchOpen(false)}>
                                            <span className="flex items-center gap-3">
                                                {renderIcon(res.type, res.entity)}
                                                <span className="font-medium">
                                                    {searchableEntityNameSelectors[res.type]?.(res.entity) ?? res.entity.id}
                                                </span>
                                            </span>
                                            <span className="text-xs text-gray-400">
                                                {searchableEntityTypeSelectors[res.type]?.(res.entity) ?? res.type}
                                            </span>
                                        </Link>
                                    )) : (
                                        <div className="p-3 text-center">
                                            Nebyly nalezeny žádné výsledky
                                        </div>
                                    ))}
                        </div>
                    </div>
                </div>
            )
            }
            <main className="max-w-6xl mx-auto mt-8 mb-8 rounded-2xl px-2 py-8 md:px-8 md:py-8 bg-white">
                {children}
                <div className="flex justify-center mt-5">
                    <button
                        className="btn-large-gray"
                        onClick={isLoggedIn ? handleLogout : handleLogin}>
                        {isLoggedIn ? <LogOut size={16} /> : <LogIn size={16} />}
                    </button>
                </div>
                <span className="flex justify-center mt-3 font-size-xs text-gray-400">
                    {username}
                </span>
            </main>
        </div>
    )
}

async function requestPermissions() {
    if (!("Notification" in window)) {
        return
    }

    if (Notification.permission === "default") {
        await Notification.requestPermission()
    }

    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(() => { }, () => { })
    }
}