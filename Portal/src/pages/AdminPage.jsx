import { useEffect, useMemo, useState } from "react"
import { useAuth } from "../contexts/AuthContext"
import TabMenu from "../components/TabMenu"
import TripSummary from "../components/TripSummary"
import NoteBar from "../components/NoteBar"
import { useUpcomingOrCurrentTrip } from "../hooks/useUpcomingOrCurrentTrip"
import ExpenseSummary from "../components/ExpenseSummary"
import { Plus } from "lucide-react"
import showFormToast from "../components/FormToast"
import FloatingButton from "../components/FloatingButton"
import { useDataConsistencyIssues } from "../hooks/useDataConsistencyIssues"
import { fromZonedTime } from "date-fns-tz"
import { useRegularTrips } from "../hooks/useRegularTrips"
import FlightCardGrid from "../components/FlightCardGrid"
import DataConsistencyIssueCardGrid from "../components/DataConsistencyIssueCardGrid"
import { useAirlines } from "../hooks/useAirlines"
import { useEvents } from "../hooks/useEvents"
import ConfigurationEditor from "../components/ConfigurationEditor"
import { useConfiguration } from "../contexts/ConfigContext"
import DeviceCardGrid from "../components/DeviceCardGrid"
import { useDevices } from "../hooks/useDevices"
import AirlineCardGrid from "../components/AirlineCardGrid"
import showInputToast from "../components/InputToast"
import { useAirports } from "../hooks/useAirports"
import {
    createScheduledFlight, createWatchedFlight, getCoordinates, createAirlineCode, refreshPlaceAlbum, updateCategoryMetadata,
    listRegularPlaces, createGeographicalExtensionCategory, removeCandidatePlace, logFlight, replaceFitness
} from "../clients/coreClient"

// TODO: Make it dynamic - if the sub-page has nothing to show, hide the label.
const labels = ["Aktuální výlet", "Sledované lety", "Aerolinky", "Hlášené problémy", "Konfigurace", "Zařízení"]

export default function AdminPage() {
    const { isAdmin } = useAuth()
    const { publishAllAlbumsInvalidatedEvent } = useEvents()
    const { configuration, updateConfigurationEntry } = useConfiguration()

    const dataConsistencyIssues = useDataConsistencyIssues()
    const { airlines, createAirline, updateAirlineName, updateAirlineLogo, removeAirline, removeAirlineCode } = useAirlines()
    const { updateAirportLongName } = useAirports()
    const devices = useDevices({ type: "agent" })
    const trips = useRegularTrips({ include: "watchedFlights" })
    const { trip: upcomingOrCurrentTrip, createTripNote, removeTripNote, createTripExpense,
        updateTripExpenseDescription, updateTripExpenseValue, removeTripExpense } = useUpcomingOrCurrentTrip()

    const getAirportTimezone = async (airportName) => (await getCoordinates("Letiště " + airportName))?.timezone
    const getAirportLocalTime = async (airportName, time) => Math.round(fromZonedTime(time, await getAirportTimezone(airportName))?.getTime() / 1000)

    const [activeTab, setActiveTab] = useState(() => {
        const saved = sessionStorage.getItem("adminPageActiveTab")
        return saved !== null ? Number(saved) : 0
    })

    useEffect(() => {
        sessionStorage.setItem("adminPageActiveTab", activeTab)
    }, [activeTab])

    const watchedFlights = useMemo(() => {
        const filteredFlights = trips?.flatMap(trip => trip.watchedFlights ?? []);
        return filteredFlights && [...filteredFlights].sort((a, b) => a.start - b.start)
    }, [trips])

    const handleFlightCreated = () => {
        showFormToast(
            "Zadej údaje o letu k přidání:",
            [
                { label: "Číslo letu", required: true, placeholder: "EK139" },
                { label: "Místo odletu", required: true, placeholder: "Praha" },
                { label: "Čas odletu (v časové zóně místa odletu)", required: true, type: "datetime-local" },
                { label: "Místo příletu", required: true, placeholder: "Dubaj" },
                { label: "Čas příletu (v časové zóně místa příletu)", required: true, type: "datetime-local" },
                {
                    label: "Typ", required: true, type: "select", options: [
                        { id: "watched", name: "Sledovaný" },
                        { id: "scheduled", name: "Potvrzený" }
                    ]
                }
            ],
            "Let byl úspěšně přidán",
            "Při přidávání letu došlo k chybě",
            async (flight, from, scheduledDeparture, to, scheduledArrival, type) => {
                if (type === "scheduled") {
                    return createScheduledFlight(flight, from, to, await getAirportLocalTime(from, scheduledDeparture), await getAirportLocalTime(to, scheduledArrival))
                }
                else if (type === "watched") {
                    return createWatchedFlight(flight, from, to, await getAirportLocalTime(from, scheduledDeparture), await getAirportLocalTime(to, scheduledArrival))
                }
                else {
                    return Promise.reject(`Unknown flight type '${type}'.`)
                }
            }
        )
    }

    const handleAirlineCreated = () => {
        showInputToast("Zadej název aerolinky k přidání:",
            "",
            "Aerolinka byla úspěšně přidána",
            "Při přidávání aerolinky došlo k chybě",
            createAirline
        )
    }

    return isAdmin && (
        <>
            <TabMenu
                labels={labels}
                activeTab={activeTab}
                setActiveTab={setActiveTab} />
            {activeTab === 0 && (
                <>
                    <TripSummary tripId={upcomingOrCurrentTrip?.id} />
                    <NoteBar
                        notes={upcomingOrCurrentTrip && (upcomingOrCurrentTrip.notes ?? [])}
                        onNoteCreated={createTripNote}
                        onNoteRemoved={removeTripNote} />
                    <ExpenseSummary
                        expenses={upcomingOrCurrentTrip && (upcomingOrCurrentTrip.expenses ?? [])}
                        onExpenseCreated={createTripExpense}
                        onExpenseDescriptionUpdated={updateTripExpenseDescription}
                        onExpenseValueUpdated={updateTripExpenseValue}
                        onExpenseRemoved={removeTripExpense} />
                </>
            )}
            {activeTab === 1 && (
                <>
                    <FlightCardGrid flights={watchedFlights} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handleFlightCreated} />
                </>
            )}
            {activeTab === 2 && (
                <>
                    <AirlineCardGrid
                        airlines={airlines}
                        onAirlineRemoved={removeAirline}
                        onAirlineNameUpdated={updateAirlineName}
                        onAirlineLogoUpdated={updateAirlineLogo}
                        onAirlineCodeRemoved={removeAirlineCode} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handleAirlineCreated} />
                </>
            )}
            {activeTab === 3 && (
                <DataConsistencyIssueCardGrid
                    dataConsistencyIssues={dataConsistencyIssues}
                    airlines={airlines}
                    onAirlineCodeAssigned={createAirlineCode}
                    onFitnessReplaced={replaceFitness}
                    onAirlineLogoChanged={updateAirlineLogo}
                    onAirportNameChanged={updateAirportLongName}
                    onAllAlbumsInvalidated={publishAllAlbumsInvalidatedEvent}
                    onPhotoInvalidated={photoId => listRegularPlaces({ photoId: photoId, include: "dates" })
                        .then(places => Promise.all(places.flatMap(place => place.dates.map(date => refreshPlaceAlbum(place.id, date.album.id)))))}
                    onGeographicalExtensionCategoryAdded={createGeographicalExtensionCategory}
                    onPlaceRemoved={removeCandidatePlace}
                    onFlightLogged={logFlight}
                    onCategoryMetadataChanged={updateCategoryMetadata}
                    onRegionManagementOpened={() => { /** TODO: Set active tab to the region management. */ }} />
            )}
            {activeTab === 4 && (
                <ConfigurationEditor
                    configuration={configuration}
                    onConfigurationUpdated={updateConfigurationEntry} />
            )}
            {activeTab === 5 && (
                <>
                    <DeviceCardGrid devices={devices} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handleFlightCreated} />
                </>
            )}
        </>
    )
}