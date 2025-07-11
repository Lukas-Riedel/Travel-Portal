import MainLayout from "./layouts/MainLayout"
import { BrowserRouter, Route, Routes, useLocation, useSearchParams } from "react-router-dom"
import { useAuth } from "./contexts/AuthContext"
import { useEffect } from "react"
import { Toaster } from "sonner"
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
import { getToken, onMessage } from "firebase/messaging"
import { messaging } from "./lib/firebase"
import { useApi } from "./hooks/useApi"

function AppContent() {
    const { accessToken, login } = useAuth()
    const { createDevice } = useApi()

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

    useEffect(() => {
        if ("serviceWorker" in navigator) {
            navigator.serviceWorker.register((import.meta.env.VITE_BASE_PATH || "") + "/firebase-messaging-sw.js", { type: "module" })
                .then(registration => getToken(messaging, {
                    vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY,
                    serviceWorkerRegistration: registration
                }))
                .then(createDevice)
                .catch(e => console.log(e))
        }

        onMessage(messaging, payload => {
            // TODO: Handle the message.
            console.log("Message received:", payload)
        })
    }, [])


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
            <Route path="/category/:categoryId" element={<MainLayout><CategoryPage /></MainLayout>} />
            <Route path="/label/:labelName" element={<MainLayout><LabelPage /></MainLayout>} />
            <Route path="/flight" element={<MainLayout><FlightsPage /></MainLayout>} />
            <Route path="/airport/:airportId" element={<MainLayout><AirportPage /></MainLayout>} />
            <Route path="/airline/:airlineName" element={<MainLayout><AirlinePage /></MainLayout>} />
            <Route path="/tracker" element={<MainLayout><TrackerPage /></MainLayout>} />
            <Route path="/plan" element={<MainLayout><PlansPage /></MainLayout>} />
            <Route path="/plan/place/:placeId" element={<MainLayout><PlacePage /></MainLayout>} />
            <Route path="/plan/category/:categoryId" element={<MainLayout><CandidateCategoryPage /></MainLayout>} />
            <Route path="/plan/label/:labelName" element={<MainLayout><CandidateLabelPage /></MainLayout>} />
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