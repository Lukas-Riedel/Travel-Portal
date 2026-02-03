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
import { formatNewProblems } from "./utils/formatters"
import PlaceHighlightsPage from "./pages/PlaceHighlightsPage"
import TripHighlightsPage from "./pages/TripHighlightsPage"
import CategoryHighlightsPage from "./pages/CategoryHighlightsPage"
import YearHighlightsPage from "./pages/YearHighlightsPage"
import { format, fromUnixTime } from "date-fns"
import { toZonedTime } from "date-fns-tz"
import StatisticsPage from "./pages/StatisticsPage"
import { TailSpin } from "react-loader-spinner"
import { UserRole } from "./types/CoreSwaggerTypes.ts"

export default function App() {
    const { events: newDataConsistencyIssuesDetectedEvents } = useEvents("NewDataConsistencyIssuesDetected")
    useEffect(() => {
        newDataConsistencyIssuesDetectedEvents.forEach(event => {
            event.markAsRead()

            toast.success(`Hlášeno ${formatNewProblems(event.count)}`)
        })
    }, [newDataConsistencyIssuesDetectedEvents])

    const { events: flightLoggedEvents } = useEvents("FlightLogged")
    useEffect(() => {
        flightLoggedEvents.forEach(event => {
            event.markAsRead()

            toast.success(`Let ${event.flight} přistál na letišti ${event.to} v ${format(toZonedTime(fromUnixTime(event.actualArrival), event.timezone), "HH:mm")} místního času`)
        })
    }, [flightLoggedEvents])

    const { events: processingStartedEvents } = useEvents("ProcessingStarted")
    useEffect(() => {
        processingStartedEvents.forEach(event => {
            event.markAsRead()

            if (event.name === "PhotosUploadingTriggered") {
                toast.success(`Nahrávání fotek pro místo ${event.args.placeName} bylo zahájeno`)
            }
            else if (event.name === "PhotoReplacingTriggered") {
                toast.success(`Nahrazování fotky pro místo ${event.args.placeName} bylo zahájeno`)
            }
            else if (event.name === "HighlightsSelectingTriggered" && event.args.isExplicit) {
                if (event.args.highlightType === "place") {
                    toast.success(`Vybírání highlightů pro místo ${event.args.entityName} bylo zahájeno`)
                }
                else if (event.args.highlightType === "trip") {
                    toast.success(`Vybírání highlightů pro výlet ${event.args.entityName} bylo zahájeno`)
                }
                else if (event.args.highlightType === "category") {
                    toast.success(`Vybírání highlightů pro kategorii ${event.args.entityName} bylo zahájeno`)
                }
                else if (event.args.highlightType === "year") {
                    toast.success(`Vybírání highlightů pro rok ${event.args.entityName} bylo zahájeno`)
                }
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
            else if (event.name === "HighlightsSelectingTriggered" && event.args.isExplicit) {
                if (event.args.highlightType === "place") {
                    toast.success(`Vybírání highlightů pro místo ${event.args.entityName} bylo dokončeno`)
                }
                else if (event.args.highlightType === "trip") {
                    toast.success(`Vybírání highlightů pro výlet ${event.args.entityName} bylo dokončeno`)
                }
                else if (event.args.highlightType === "category") {
                    toast.success(`Vybírání highlightů pro kategorii ${event.args.entityName} bylo dokončeno`)
                }
                else if (event.args.highlightType === "year") {
                    toast.success(`Vybírání highlightů pro rok ${event.args.entityName} bylo dokončeno`)
                }
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
            else if (event.name === "HighlightsSelectingTriggered" && event.args.isExplicit) {
                if (event.args.highlightType === "place") {
                    toast.success(`Vybírání highlightů pro místo ${event.args.entityName} se nezdařilo`)
                }
                else if (event.args.highlightType === "trip") {
                    toast.success(`Vybírání highlightů pro výlet ${event.args.entityName} se nezdařilo`)
                }
                else if (event.args.highlightType === "category") {
                    toast.success(`Vybírání highlightů pro kategorii ${event.args.entityName} se nezdařilo`)
                }
                else if (event.args.highlightType === "year") {
                    toast.success(`Vybírání highlightů pro rok ${event.args.entityName} se nezdařilo`)
                }
            }
        })
    }, [processingFailedEvents])

    return (
        <>
            <Toaster position="top-center" offset={96} />
            <BrowserRouter basename={"/"}>
                <ScrollToTop />
                <AppContent />
            </BrowserRouter>
        </>
    )
}

function AppContent() {
    const { accessToken, hasRole } = useAuth()

    // TODO: Find a better rule for redirect to the admin page.
    return accessToken ? (
        <Routes>
            <Route path="/" element={<Navigate to={hasRole(UserRole.ConfigurationEdit) ? "/admin" : "/feed"} replace />} />
            <Route path="/feed" element={<MainLayout><RecentPlacesPage /></MainLayout>} />
            <Route path="/statistics" element={<MainLayout><StatisticsPage /></MainLayout>} />
            <Route path="/trip" element={<MainLayout><YearsPage /></MainLayout>} />
            <Route path="/trip/:tripId" element={<MainLayout><TripPage /></MainLayout>} />
            <Route path="/trip/:tripId/highlight" element={<MainLayout><TripHighlightsPage /></MainLayout>} />
            <Route path="/year/:year" element={<MainLayout><YearPage /></MainLayout>} />
            <Route path="/year/:year/highlight" element={<MainLayout><YearHighlightsPage /></MainLayout>} />
            <Route path="/place" element={<MainLayout><CountriesPage /></MainLayout>} />
            <Route path="/place/:placeId" element={<MainLayout><PlacePage /></MainLayout>} />
            <Route path="/place/:placeId/album/:albumId" element={<MainLayout><AlbumPage /></MainLayout>} />
            <Route path="/place/:placeId/highlight" element={<MainLayout><PlaceHighlightsPage /></MainLayout>} />
            <Route path="/category/:categoryId" element={<MainLayout><CategoryPage /></MainLayout>} />
            <Route path="/category/:categoryId/highlight" element={<MainLayout><CategoryHighlightsPage /></MainLayout>} />
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
    ) : (
        <MainLayout>
            <div className="p-16 flex items-center justify-center">
                <TailSpin
                    color="black"
                    height={128}
                    width={128} />
            </div>
        </MainLayout>
    )
}

function ScrollToTop() {
    const { pathname } = useLocation()

    useEffect(() => {
        window.scrollTo(0, 0)
    }, [pathname])

    return null
}