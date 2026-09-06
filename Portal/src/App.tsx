import MainLayout from "./layouts/MainLayout.tsx"
import { BrowserRouter, Navigate, Route, Routes, useLocation } from "react-router-dom"
import { useAuth } from "./contexts/AuthContext.tsx"
import { useEffect } from "react"
import { toast, Toaster } from "sonner"
import CountriesPage from "./pages/CountriesPage.jsx"
import PlacePage from "./pages/PlacePage.jsx"
import YearsPage from "./pages/YearsPage.jsx"
import TripPage from "./pages/TripPage.jsx"
import CategoryPage from "./pages/CategoryPage.jsx"
import LabelPage from "./pages/LabelPage.jsx"
import YearPage from "./pages/YearPage.jsx"
import TrackerPage from "./pages/TrackerPage.jsx"
import FlightsPage from "./pages/FlightsPage.jsx"
import AirportPage from "./pages/AirportPage.jsx"
import AirlinePage from "./pages/AirlinePage.jsx"
import PlansPage from "./pages/PlansPage.jsx"
import CandidateCategoryPage from "./pages/CandidateCategoryPage.jsx"
import CandidateLabelPage from "./pages/CandidateLabelPage.jsx"
import { useEvents } from "./hooks/useEvents.ts"
import AlbumPage from "./pages/AlbumPage.jsx"
import AdminPage from "./pages/AdminPage.jsx"
import RecentPlacesPage from "./pages/RecentPlacesPage.jsx"
import PlaceHighlightsPage from "./pages/PlaceHighlightsPage.jsx"
import TripHighlightsPage from "./pages/TripHighlightsPage.jsx"
import CategoryHighlightsPage from "./pages/CategoryHighlightsPage.jsx"
import YearHighlightsPage from "./pages/YearHighlightsPage.jsx"
import { format, fromUnixTime } from "date-fns"
import { toZonedTime } from "date-fns-tz"
import StatisticsPage from "./pages/StatisticsPage.jsx"
import { TailSpin } from "react-loader-spinner"
import { UserRole } from "./types/CoreSwaggerTypes.ts"
import { useFormatters } from "./hooks/useFormatters.ts"
import { EventType } from "./types/EventType.ts"
import { useTranslation } from "react-i18next"
import { formatTimestamp } from "./utils/timeUtils.ts"

export default function App() {
    const { t } = useTranslation()
    const { formatNewProblems } = useFormatters()

    const { events: newDataConsistencyIssuesDetectedEvents } = useEvents(EventType.NewDataConsistencyIssuesDetected)
    useEffect(() => {
        newDataConsistencyIssuesDetectedEvents.forEach(event => {
            event.markAsRead()

            toast.success(t("notification.newDataConsistencyIssuesDetected", { formattedProblems: formatNewProblems(event.args.count) }))
        })
    }, [newDataConsistencyIssuesDetectedEvents])

    const { events: taskDeadlineReachedEvents } = useEvents(EventType.TaskDeadlineReached)
    useEffect(() => {
        taskDeadlineReachedEvents.forEach(event => {
            event.markAsRead()

            toast.success(event.args.task)
        })
    }, [taskDeadlineReachedEvents])

    const { events: flightLoggedEvents } = useEvents(EventType.FlightLogged)
    useEffect(() => {
        flightLoggedEvents.forEach(event => {
            event.markAsRead()

            toast.success(t("notification.flightLogged", { flight: event.args.flight, airport: event.args.to, formattedLocalTime: formatTimestamp(event.args.actualArrival, t("general.format.time")) }))
        })
    }, [flightLoggedEvents])

    const { events: flightReminderReceivedEvents } = useEvents(EventType.FlightReminderReceived)
    useEffect(() => {
        flightReminderReceivedEvents.forEach(event => {
            event.markAsRead()

            toast.success(event.args.text)
        })
    }, [flightReminderReceivedEvents])

    const { events: processingStartedEvents } = useEvents(EventType.ProcessingStarted)
    useEffect(() => {
        processingStartedEvents.forEach(event => {
            event.markAsRead()

            if (event.name === EventType.PhotosUploadingTriggered && event.args.sendNotification) {
                toast.success(t("notification.processingStarted.photosUploadingTriggered", { placeName: event.args.placeName }))
            }
            else if (event.name === EventType.PhotoReplacingTriggered && event.args.sendNotification) {
                toast.success(t("notification.processingStarted.photoReplacingTriggered", { placeName: event.args.placeName }))
            }
        })
    }, [processingStartedEvents])

    const { events: processingEndedEvents } = useEvents(EventType.ProcessingEnded)
    useEffect(() => {
        processingEndedEvents.forEach(event => {
            event.markAsRead()

            if (event.name === EventType.PhotosUploadingTriggered && event.args.sendNotification) {
                toast.success(t("notification.processingEnded.photosUploadingTriggered", { placeName: event.args.placeName }))
            }
            else if (event.name === EventType.PhotoReplacingTriggered && event.args.sendNotification) {
                toast.success(t("notification.processingEnded.photoReplacingTriggered", { placeName: event.args.placeName }))
            }
        })
    }, [processingEndedEvents])

    const { events: processingFailedEvents } = useEvents(EventType.ProcessingFailed)
    useEffect(() => {
        processingFailedEvents.forEach(event => {
            event.markAsRead()

            if (event.name === EventType.PhotosUploadingTriggered && event.args.sendNotification) {
                toast.success(t("notification.processingFailed.photosUploadingTriggered", { placeName: event.args.placeName }))
            }
            else if (event.name === EventType.PhotoReplacingTriggered && event.args.sendNotification) {
                toast.success(t("notification.processingFailed.photoReplacingTriggered", { placeName: event.args.placeName }))
            }
        })
    }, [processingFailedEvents])

    return (
        <>
            <Toaster position="top-center" offset={96} />
            <BrowserRouter basename={"/"}>
                <AppContent />
            </BrowserRouter>
        </>
    )
}

function AppContent() {
    const { accessToken, hasRole } = useAuth()
    const { pathname } = useLocation()

    useEffect(() => {
        window.scrollTo(0, 0)
    }, [pathname])

    // TODO: Find a better rule for redirect to the admin page.
    // TODO: Path prefixes are duplicated in navigationUtils.ts - find a common place for them.
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