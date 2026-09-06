import { Backpack, Calendar, CircleQuestionMark, Earth, House, Landmark, LocateFixed, LogIn, LogOut, Map as MapIcon, MapPin, Pin, Plane, PlaneIcon, Search, Tag, TowerControl, TreePalm, Waves, XIcon } from "lucide-react"
import { Link, useLocation } from "react-router-dom"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useEffect, useMemo, useState } from "react"
import { useTranslation } from "react-i18next"
import { CategoryCategory, IndexableEntityType, UserRole, type AirlineIdentifier, type AirportIdentifier, type CategoryIdentifier, type Highlight, type Label, type PlaceIdentifier, type SearchResult, type TripIdentifier, type YearIdentifier } from "../types/CoreSwaggerTypes.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { search } from "../clients/coreClient.ts"
import { useCategories } from "../hooks/useCategories.ts"
import PhotoTile from "../components/PhotoTile.js"
import { getEntityPrettyName, getTripFullName } from "../utils/formattingUtils.ts"
import { useCountryCategoriesMap } from "../hooks/useCountryCategoriesMap.ts"
import type { Indexable } from "../types/Indexable.ts"
import CategoryFlag from "../components/CategoryFlag.tsx"
import AppLink from "../components/AppLink.tsx"
import { StaticNavigationTarget } from "../types/StaticNavigationTarget.ts"
import { getPath } from "../utils/navigationUtils.ts"
import LoadingTile from "../components/LoadingTile.tsx"

const SEARCHABLE_ENTITY_ICON_SELECTORS = {
    [IndexableEntityType.Category]: (entity: Indexable) => CATEGORY_CATEGORY_ICONS[(entity as CategoryIdentifier).category] ?? LocateFixed,
    [IndexableEntityType.Place]: _ => MapPin,
    [IndexableEntityType.Airport]: _ => TowerControl,
    [IndexableEntityType.Airline]: _ => PlaneIcon,
    [IndexableEntityType.Label]: _ => Tag,
    [IndexableEntityType.Trip]: _ => Backpack,
    [IndexableEntityType.Year]: _ => Calendar
}

const CATEGORY_CATEGORY_ICONS = {
    [CategoryCategory.Continent]: Earth,
    [CategoryCategory.Country]: MapIcon,
    [CategoryCategory.Administrative]: Landmark,
    [CategoryCategory.Ocean]: Waves,
    [CategoryCategory.Sea]: Waves,
    [CategoryCategory.Bay]: Waves,
    [CategoryCategory.Island]: TreePalm,
    [CategoryCategory.Region]: MapIcon
}

// TODO: Introduce getEntityPrettyName for Indexable - similary to the getPath function for Navigable.
const SEARCHABLE_ENTITY_NAME_SELECTORS = {
    [IndexableEntityType.Category]: (entity: Indexable) => getEntityPrettyName((entity as CategoryIdentifier).name),
    [IndexableEntityType.Place]: (entity: Indexable) => getEntityPrettyName((entity as PlaceIdentifier).name),
    [IndexableEntityType.Airport]: (entity: Indexable) => (entity as AirportIdentifier).longName,
    [IndexableEntityType.Airline]: (entity: Indexable) => (entity as AirlineIdentifier).name,
    [IndexableEntityType.Label]: (entity: Indexable) => (entity as Label).name,
    [IndexableEntityType.Trip]: (entity: Indexable) => getTripFullName(entity as TripIdentifier),
    [IndexableEntityType.Year]: (entity: Indexable) => (entity as YearIdentifier).id
}

const DEFAULT_FOUND_ENTITIES_COUNT = 10
const DEFAULT_FOUND_HIGHLIGHTS_COUNT = 6

interface MainLayoutProps {
    children: React.ReactNode
}

export default function MainLayout({ children }: MainLayoutProps) {
    const { isLoggedIn, username, login, logout, hasRole } = useAuth()
    const location = useLocation()
    const { t } = useTranslation()
    const { showLogoutToast, showLoginToast } = usePredefinedUserInput()

    const [isMenuOpen, setIsMenuOpen] = useState(false)
    const [isSearchOpen, setIsSearchOpen] = useState(false)
    const [foundEntities, setFoundEntities] = useState<SearchResult[] | null>(null)
    const [foundHighlights, setFoundHighlights] = useState<SearchResult[] | null>(null)
    const [searchedText, setSearchedText] = useState<string | null>(null)

    const countryCategoriesMap = useCountryCategoriesMap()

    // TODO: Handle allowed prefixes in a better way (i.e., through the getPath function somehow).
    const navigationItems = [
        { label: t("menu.label.search"), requiredRole: UserRole.SearchRead, onClick: () => setIsSearchOpen(true) },
        { label: t("menu.label.feed"), to: StaticNavigationTarget.Feed, requiredRole: UserRole.PlaceRead, allowedPrefixes: ["/feed"] },
        { label: t("menu.label.trips"), to: StaticNavigationTarget.Trips, requiredRole: UserRole.TripRead, allowedPrefixes: ["/trip", "/year"] },
        { label: t("menu.label.places"), to: StaticNavigationTarget.Places, requiredRole: UserRole.PlaceRead, allowedPrefixes: ["/place", "/category"] },
        { label: t("menu.label.flights"), to: StaticNavigationTarget.Flights, requiredRole: UserRole.TripFlightRead, allowedPrefixes: ["/flight", "/airport", "/airline"] },
        { label: t("menu.label.statistics"), to: StaticNavigationTarget.Statistics, requiredRole: UserRole.StatisticsRead, allowedPrefixes: ["/statistics"] },
        // TODO: Find a better required role.
        { label: t("menu.label.plan"), to: StaticNavigationTarget.Plan, requiredRole: UserRole.PortalFutureRead, allowedPrefixes: ["/plan"] },
        { label: t("menu.label.tracker"), to: StaticNavigationTarget.Tracker, requiredRole: UserRole.TrackerEdit, allowedPrefixes: ["/tracker"] },
        // TODO: Find a better requried role.
        { label: t("menu.label.admin"), to: StaticNavigationTarget.Admin, requiredRole: UserRole.ConfigurationEdit, allowedPrefixes: ["/admin"] },
    ].filter(({ to, requiredRole }) => !requiredRole || hasRole(requiredRole) || (to && location.pathname.startsWith(getPath(to))))

    const handleLogin = () => {
        requestPermissions()
        showLoginToast((username, password) => login({ username, password }))
    }

    const handleLogout = () => {
        requestPermissions()
        showLogoutToast(logout)
    }

    const getIndexableEntityTypeName = (entity: Indexable, type: IndexableEntityType): string => (entity as CategoryIdentifier).category
        ? t([`category.category.${(entity as CategoryIdentifier).category}`, "general.label.category"]) : t([`general.label.${type}`, "general.label.entity"])

    useEffect(() => {
        const controller = new AbortController()

        const fetchSearch = async () => {
            if (searchedText?.length > 2) {
                try {
                    const delay = setTimeout(async () => {
                        search(searchedText, {
                            limit: DEFAULT_FOUND_ENTITIES_COUNT,
                            include: [IndexableEntityType.Category, IndexableEntityType.Place, IndexableEntityType.Airport, IndexableEntityType.Airline, IndexableEntityType.Label, IndexableEntityType.Trip, IndexableEntityType.Year]
                        }, {
                            signal: controller.signal
                        }).then(setFoundEntities)

                        search(searchedText, {
                            limit: DEFAULT_FOUND_HIGHLIGHTS_COUNT,
                            include: [IndexableEntityType.Highlight]
                        }, {
                            signal: controller.signal
                        }).then(setFoundHighlights)
                    }, 200);

                    return () => clearTimeout(delay)
                }
                catch (e) {
                    // Do nothing.
                }
            }
            else {
                setFoundEntities(null)
                setFoundHighlights(null)
            }
        }

        fetchSearch()

        return () => controller.abort()
    }, [searchedText])

    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === "Escape") {
                setIsSearchOpen(false)
            }
            if ((e.metaKey || e.ctrlKey) && e.key.toUpperCase() === "K") {
                e.preventDefault()
                setIsSearchOpen(true)
            }
        }

        window.addEventListener("keydown", handleKeyDown)
        return () => window.removeEventListener("keydown", handleKeyDown)
    }, [])

    const renderEntityIcon = (entity: Indexable, type: IndexableEntityType) => {
        let countryName = undefined;
        if (type === IndexableEntityType.Place) {
            countryName = (entity as PlaceIdentifier).country
        }
        else if (type === IndexableEntityType.Airport) {
            countryName = (entity as AirportIdentifier).country
        }
        else if (type === IndexableEntityType.Category && (entity as CategoryIdentifier).category === CategoryCategory.Country) {
            countryName = (entity as CategoryIdentifier).name
        }

        if (countryName) {
            return (
                <div className="w-5 h-5 flex items-center justify-center overflow-hidden rounded-sm flex-shrink-0">
                    <CategoryFlag
                        category={countryCategoriesMap.get(countryName)}
                        className="w-full h-full object-cover shadow-sm" />
                </div>
            )
        }

        const Icon = SEARCHABLE_ENTITY_ICON_SELECTORS[type]?.(entity) ?? CircleQuestionMark
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
                    <AppLink to={StaticNavigationTarget.Home}>
                        <img
                            src="/icon.svg"
                            className="h-8 w-8 hidden md:block" />
                    </AppLink>
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
                        {navigationItems.map(({ label, to, onClick, allowedPrefixes }) => {
                            const isActive = allowedPrefixes?.some(prefix => location.pathname.startsWith(prefix))
                            return to ? (
                                <AppLink
                                    key={label}
                                    to={to}
                                    onClick={onClick}
                                    className={`relative inline-block px-2 py-1 font-medium transition-colors duration-200
                                        ${isActive
                                            ? "text-blue-700 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-full after:bg-blue-700"
                                            : "text-gray-700 hover:text-blue-700 hover:after:absolute hover:after:left-0 hover:after:bottom-0 hover:after:h-0.5 hover:after:w-full hover:after:bg-blue-600"
                                        }`}>
                                    {label}
                                </AppLink>
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
                            {navigationItems.map(({ label, to, onClick }) => (
                                <li
                                    key={label}
                                    className="w-full">
                                    {to ? (
                                        <AppLink
                                            to={to}
                                            className="block w-full px-3 py-2 rounded hover:bg-gray-100"
                                            onClick={() => {
                                                if (onClick) {
                                                    onClick()
                                                }
                                                setIsMenuOpen(false)
                                            }}>
                                            {label}
                                        </AppLink>
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
                    className="fixed inset-0 z-[60] flex items-start justify-center pt-[8vh] bg-gray-900/50 backdrop-blur-sm"
                    onClick={() => setIsSearchOpen(false)}>
                    <div
                        className="w-full md:max-w-4xl bg-white rounded-xl shadow-2xl overflow-hidden"
                        onClick={e => e.stopPropagation()}>
                        <div className="flex items-center p-4 border-b">
                            <Search className="text-gray-400 mr-3" />
                            <input
                                autoFocus
                                className="w-full outline-none text-lg"
                                placeholder="Zadej hledaný výraz"
                                value={searchedText}
                                onChange={e => setSearchedText(e.target.value)} />
                            <button
                                onClick={() => setIsSearchOpen(false)}
                                className="text-xs p-1 bg-gray-100 rounded">
                                <XIcon size={16} />
                            </button>
                        </div>
                        <div className="max-h-[80vh] overflow-y-auto p-2">
                            {foundEntities && (
                                <>
                                    <div className="px-3 pt-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400 flex items-center gap-2">
                                        <span>
                                            {t("search.label.result.text")}
                                        </span>
                                        <div className="h-px bg-gray-100 flex-grow" />
                                    </div>
                                    {foundEntities.length ?
                                        foundEntities.map(searchResult => (
                                            // TODO: Using searchResult.type to obtain the link is a hack. Change to use AppLink (currently, there's no way to distinguish between AirlineIdentifier and Label, though).
                                            <Link
                                                key={searchResult.entity.id}
                                                className="p-3 hover:bg-blue-50 rounded-lg cursor-pointer flex justify-between"
                                                to={`/${searchResult.type}/${searchResult.entity.id}`}
                                                onClick={() => setIsSearchOpen(false)}>
                                                <span className="flex items-center gap-3">
                                                    {renderEntityIcon(searchResult.entity, searchResult.type)}
                                                    <span className="font-medium">
                                                        {SEARCHABLE_ENTITY_NAME_SELECTORS[searchResult.type]?.(searchResult.entity) ?? searchResult.entity.id}
                                                    </span>
                                                </span>
                                                <span className="text-xs text-gray-400">
                                                    {getIndexableEntityTypeName(searchResult.entity, searchResult.type)}
                                                </span>
                                            </Link>
                                        )) : (
                                            <div className="p-3 text-center">
                                                {t("search.label.result.empty")}
                                            </div>
                                        )}
                                </>
                            )}
                            {(foundHighlights === null || foundHighlights.length > 0) && foundEntities && (
                                <div className="mt-4">
                                    <div className="px-3 pt-2 pb-3 text-[10px] font-bold uppercase tracking-wider text-gray-400 flex items-center gap-2">
                                        <span>
                                            {t("search.label.result.visual")}
                                        </span>
                                        <div className="h-px bg-gray-100 flex-grow" />
                                    </div>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 pb-2 px-2">
                                        {foundHighlights ? (
                                            foundHighlights.map((searchResult, i) => (
                                                <PhotoTile
                                                    key={i}
                                                    className="w-full aspect-[3/2]"
                                                    firstLineText={searchResult.parent && (SEARCHABLE_ENTITY_NAME_SELECTORS[searchResult.parent.type]?.(searchResult.parent.entity) ?? searchResult.parent.entity.id)}
                                                    categories={searchResult.parent && "country" in searchResult.parent.entity && [countryCategoriesMap.get(searchResult.parent.entity.country)]}
                                                    src={(searchResult.entity as Highlight).url.thumbnail}
                                                    // TODO: Using searchResult.type to obtain the link is a hack. Change to use AppLink (currently, there's no way to distinguish between AirlineIdentifier and Label, though).
                                                    to={searchResult.parent && `/${searchResult.parent.type}/${searchResult.parent.entity.id}`}
                                                    onClick={() => setIsSearchOpen(false)} />
                                            ))
                                        ) : (
                                            Array.from({ length: DEFAULT_FOUND_HIGHLIGHTS_COUNT }, (_, i) => i + 1).map(i => (
                                                <LoadingTile
                                                    key={i}
                                                    className="w-full aspect-[3/2]" />
                                            ))
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}
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

async function requestPermissions(): Promise<void> {
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