import MainLayout from "./layouts/MainLayout"
import { BrowserRouter, Navigate, Route, Routes, useLocation } from "react-router-dom"
import { useAuth } from "./contexts/AuthContext"
import { useEffect } from "react"
import { toast, Toaster } from "sonner"
import CountriesPage from "./pages/CountriesPage"
import PlacePage from "./pages/PlacePage"
import YearsPage from "./pages/YearsPage"
import TripPage from "./pages/TripPage"
import CategoryPage from "./pages/CategoryPage"
import LabelPage from "./pages/LabelPage"
import YearPage from "./pages/YearPage"
import TrackerPage from "./pages/TrackerPage"
import FlightsPage from "./pages/FlightsPage"
import AirportPage from "./pages/AirportPage"
import AirlinePage from "./pages/AirlinePage"
import PlansPage from "./pages/PlansPage"
import CandidateCategoryPage from "./pages/CandidateCategoryPage"
import CandidateLabelPage from "./pages/CandidateLabelPage"
import { useEvents } from "./hooks/useEvents"
import AlbumPage from "./pages/AlbumPage"
import AdminPage from "./pages/AdminPage"
import RecentPlacesPage from "./pages/RecentPlacesPage"

export default function App() {
    const { events: processingStartedEvents } = useEvents("ProcessingStarted")
    useEffect(() => {
        processingStartedEvents.forEach(event => {
            event.markAsRead()

            if (event.name === "PhotosUploadingTriggered") {
                toast.success(`Nahrávání fotek pro místo ${event.args.placeName} bylo zahájeno`)
            }
        })
    }, [processingStartedEvents])

    const { events: processingEndedEvents } = useEvents("ProcessingEnded")
    useEffect(() => {
        processingEndedEvents.forEach(event => {
            event.markAsRead()

            if (event.name === "PhotosUploadingTriggered") {
                toast.success(`Nahrávání fotek pro místo ${event.args.placeName} bylo dokončeno`)
            }
            else if (event.name === "PhotoReplacingTriggered") {
                toast.success(`Nahrazování fotky pro místo ${event.args.placeName} bylo dokončeno`)
            }
        })
    }, [processingEndedEvents])

    const { events: processingFailedEvents } = useEvents("ProcessingFailed")
    useEffect(() => {
        processingFailedEvents.forEach(event => {
            event.markAsRead()

            if (event.name === "PhotosUploadingTriggered") {
                toast.success(`Nahrávání fotek pro místo ${event.args.placeName} se nezdařilo`)
            }
            else if (event.name === "PhotoReplacingTriggered") {
                toast.success(`Nahrazování fotky pro místo ${event.args.placeName} se nezdařilo`)
            }
        })
    }, [processingFailedEvents])

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

function AppContent() {
    const { accessToken, login, isAdmin } = useAuth()

    useEffect(() => {
        if (!accessToken) {
            login({ username: "guest", password: "guest" })

        }
    }, [accessToken, login])

    if (!accessToken) {
        return
    }

    return (
        <Routes>
            <Route path="/" element={<Navigate to={isAdmin ? "/admin" : "/feed"} replace />} />
            <Route path="/feed" element={<MainLayout><RecentPlacesPage /></MainLayout>} />
            <Route path="/trip" element={<MainLayout><YearsPage /></MainLayout>} />
            <Route path="/trip/:tripId" element={<MainLayout><TripPage /></MainLayout>} />
            <Route path="/year/:year" element={<MainLayout><YearPage /></MainLayout>} />
            <Route path="/place" element={<MainLayout><CountriesPage /></MainLayout>} />
            <Route path="/place/:placeId" element={<MainLayout><PlacePage /></MainLayout>} />
            <Route path="/place/:placeId/album/:albumId" element={<MainLayout><AlbumPage /></MainLayout>} />
            <Route path="/category/:categoryId" element={<MainLayout><CategoryPage /></MainLayout>} />
            <Route path="/label/:labelId" element={<MainLayout><LabelPage /></MainLayout>} />
            <Route path="/flight" element={<MainLayout><FlightsPage /></MainLayout>} />
            <Route path="/airport/:airportId" element={<MainLayout><AirportPage /></MainLayout>} />
            <Route path="/airline/:airlineId" element={<MainLayout><AirlinePage /></MainLayout>} />
            <Route path="/tracker" element={<MainLayout><TrackerPage /></MainLayout>} />
            <Route path="/plan" element={<MainLayout><PlansPage /></MainLayout>} />
            <Route path="/plan/place/:placeId" element={<MainLayout><PlacePage /></MainLayout>} />
            <Route path="/plan/category/:categoryId" element={<MainLayout><CandidateCategoryPage /></MainLayout>} />
            <Route path="/plan/label/:labelId" element={<MainLayout><CandidateLabelPage /></MainLayout>} />
            <Route path="/plan/trip/:tripId" element={<MainLayout><TripPage /></MainLayout>} />
            <Route path="/admin" element={<MainLayout><AdminPage /></MainLayout>} />
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