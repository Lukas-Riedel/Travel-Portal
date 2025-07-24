import MainLayout from "./layouts/MainLayout"
import { BrowserRouter, Route, Routes, useLocation, useSearchParams } from "react-router-dom"
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
import { useApi } from "./hooks/useApi"
import { useEvents } from "./hooks/useEvents"
import AlbumPage from "./pages/AlbumPage"

export default function App() {
    const { listRegularPlaces } = useApi()

    const { events: processingStartedEvents } = useEvents("ProcessingStarted")
    useEffect(() => {
        if (processingStartedEvents.length > 0) {
            processingStartedEvents.forEach(event => {
                event.markAsRead()

                if (event.name === "PhotosUploadingTriggered") {
                    listRegularPlaces({ albumId: event.args.albumId })
                        .then(places => places.forEach(place => {
                            toast.success(`Nahrávání fotek pro místo '${place.name}' začalo`)
                        }))
                }
            })
        }
    }, [processingStartedEvents])

    const { events: processingEndedEvents } = useEvents("ProcessingEnded")
    useEffect(() => {
        if (processingEndedEvents.length > 0) {
            processingEndedEvents.forEach(event => {
                event.markAsRead()

                if (event.name === "PhotosUploadingTriggered") {
                    listRegularPlaces({ albumId: event.args.albumId })
                        .then(places => places.forEach(place => {
                            toast.success(`Nahrávání fotek pro místo '${place.name}' bylo dokončeno`)
                        }))
                }
                else if (event.name === "PhotoReplacingTriggered") {
                    listRegularPlaces({ albumId: event.args.albumId })
                        .then(places => places.forEach(place => {
                            toast.success(`Nahrazování fotky pro místo '${place.name}' bylo dokončeno`)
                        }))
                }
            })
        }
    }, [processingEndedEvents])

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
            <Route path="/" element={<MainLayout><YearsPage /></MainLayout>} />
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