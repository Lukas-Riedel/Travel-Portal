import MainLayout from "./layouts/MainLayout"
import { BrowserRouter, Route, Routes, useLocation, useSearchParams } from "react-router-dom"
import { useAuth } from "./contexts/AuthContext"
import { useEffect } from "react"
import { Toaster } from "sonner"
import CountriesPage from "./pages/CountriesPage"
import PlacePage from "./pages/PlacePage"
import TripsPage from "./pages/TripsPage"
import TripPage from "./pages/TripPage"
import CategoryPage from "./pages/CategoryPage"
import LabelPage from "./pages/LabelPage"
import YearPage from "./pages/YearPage"
import TrackerPage from "./pages/TrackerPage"

function AppContent() {
    const { accessToken, login } = useAuth()
    const [searchParams] = useSearchParams()

    useEffect(() => {
        const apiKey = searchParams.get("apiKey")
        if (apiKey) {
            login({ apiKey })
        }

        if (!accessToken) {
            login({ username: "guest", password: "guest" })

        }
    }, [accessToken, login, searchParams])

    if (!accessToken) {
        return
    }

    return (
        <Routes>
            <Route path="/" element={<MainLayout><CountriesPage /></MainLayout>} />
            <Route path="/place" element={<MainLayout><CountriesPage /></MainLayout>} />
            <Route path="/place/:placeId" element={<MainLayout><PlacePage /></MainLayout>} />
            <Route path="/trip" element={<MainLayout><TripsPage /></MainLayout>} />
            <Route path="/trip/:tripId" element={<MainLayout><TripPage /></MainLayout>} />
            <Route path="/category/:categoryId" element={<MainLayout><CategoryPage /></MainLayout>} />
            <Route path="/label/:labelName" element={<MainLayout><LabelPage /></MainLayout>} />
            <Route path="/year/:year" element={<MainLayout><YearPage /></MainLayout>} />
            <Route path="/tracker" element={<MainLayout><TrackerPage /></MainLayout>} />
        </Routes>
    )
}

function ScrollToTop() {
    const { pathname } = useLocation()

    useEffect(() => {
        window.scrollTo(0, 0)
    }, [pathname])

    return null
}

export default function App() {
    return (
        <>
            <Toaster position="top-center" offset={96} />
            <BrowserRouter basename={import.meta.env.VITE_BASE_PATH || "/"}>
                <ScrollToTop />
                <AppContent />
            </BrowserRouter>
        </>
    )
}