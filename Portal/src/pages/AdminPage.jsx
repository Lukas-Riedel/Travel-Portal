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
    listRegularPlaces, createGeographicalExtensionRegion, removeCandidatePlace, logFlight, replaceFitness
} from "../clients/coreClient"
import PlaceCardGrid from "../components/PlaceCardGrid"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { useSubscriptions } from "../hooks/useSubscriptions"
import SubscriptionCardGrid from "../components/SubscriptionCardGrid"

// TODO: Duplicated in ExpenseSummary.
const currencies = ["AED", "AFN", "ALL", "AMD", "ANG", "AOA", "ARS", "AUD", "AWG", "AZN", "BAM", "BBD", "BDT", "BGN", "BHD", "BIF", "BMD", "BND", "BOB", "BRL", "BSD", "BTN", "BWP", "BYN", "BZD", "CAD", "CDF", "CHF", "CLP", "CNY", "COP", "CRC", "CUP", "CVE", "CZK", "DJF", "DKK", "DOP", "DZD", "EGP", "ERN", "ETB", "EUR", "FJD", "FKP", "FOK", "GBP", "GEL", "GGP", "GHS", "GIP", "GMD", "GNF", "GTQ", "GYD", "HKD", "HNL", "HRK", "HTG", "HUF", "IDR", "ILS", "IMP", "INR", "IQD", "IRR", "ISK", "JEP", "JMD", "JOD", "JPY", "KES", "KGS", "KHR", "KID", "KMF", "KRW", "KWD", "KYD", "KZT", "LAK", "LBP", "LKR", "LRD", "LSL", "LYD", "MAD", "MDL", "MGA", "MKD", "MMK", "MNT", "MOP", "MRU", "MUR", "MVR", "MWK", "MXN", "MYR", "MZN", "NAD", "NGN", "NIO", "NOK", "NPR", "NZD", "OMR", "PAB", "PEN", "PGK", "PHP", "PKR", "PLN", "PYG", "QAR", "RON", "RSD", "RUB", "RWF", "SAR", "SBD", "SCR", "SDG", "SEK", "SGD", "SHP", "SLE", "SLL", "SOS", "SRD", "SSP", "STN", "SYP", "SZL", "THB", "TJS", "TMT", "TND", "TOP", "TRY", "TTD", "TVD", "TWD", "TZS", "UAH", "UGX", "USD", "UYU", "UZS", "VES", "VND", "VUV", "WST", "XAF", "XCD", "XDR", "XOF", "XPF", "YER", "ZAR", "ZMW", "ZWL"]

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
    const { places: permanentPlaces, createPermanentPlace, removePermanentPlace } = useRegularPlaces({ include: "categories", minStart: 0, maxEnd: 0 })
    const { subscriptions, createSubscription, removeSubscription } = useSubscriptions()

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


    const labels = [
        {
            name: "Aktuální výlet",
            enabled: upcomingOrCurrentTrip !== null
        },
        {
            name: "Sledované lety",
            enabled: watchedFlights && watchedFlights.length > 0
        },
        {
            name: "Aerolinky",
            enabled: airlines && airlines.length > 0
        },
        {
            name: "Hlášené problémy",
            enabled: dataConsistencyIssues && dataConsistencyIssues.length > 0
        },
        {
            name: "Konfigurace",
            enabled: configuration !== null
        },
        {
            name: "Zařízení",
            enabled: devices && devices.length > 0
        },
        {
            name: "Trvalá místa",
            enabled: permanentPlaces && permanentPlaces.length > 0
        },
        {
            name: "Aktivní předplatná",
            enabled: subscriptions && subscriptions.length > 0
        }
    ]

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

    const handleSubscriptionCreated = () => {
        showFormToast(
            "Zadej údaje o předplatném k přidání:",
            [
                { label: "Popis", required: true },
                { label: "Hodnota", required: true, type: "number", min: 0 },
                { label: "Měna", required: true, type: "select", options: currencies.map(currency => ({ id: currency, name: currency })) },
                { label: "Expirace", required: true, type: "datetime-local" }
            ],
            "Předplatné bylo úspěšně přidáno",
            "Při přidávání předplatného došlo k chybě",
            async (description, value, Currency, expiration) => {
                const convertedExpiration = Math.round(new Date(expiration).getTime() / 1000)
                if (convertedExpiration < Date.now() / 1000) {
                    return Promise.reject("Expiration must be in the future.")
                }

                return createSubscription(description, value, Currency, convertedExpiration)
            }
        )
    }

    const handlePermanentPlaceCreated = () => {
        showFormToast(
            "Zadej údaje o místě k přidání:",
            [
                { label: "Jméno", required: true },
                { label: "Adresa", required: false }
            ],
            "Místo bylo úspěšně přidáno",
            "Při přidávání místa došlo k chybě",
            async (name, address) => createPermanentPlace(name, address || name)
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
                    <TripSummary
                        trip={upcomingOrCurrentTrip}
                        onNoteAdded={createTripNote}
                        onNoteRemoved={removeTripNote}
                    />
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
                    onGeographicalExtensionCategoryAdded={createGeographicalExtensionRegion}
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
                <DeviceCardGrid devices={devices} />
            )}
            {activeTab === 6 && (
                <>
                    <PlaceCardGrid
                        places={permanentPlaces}
                        onPlaceRemoved={removePermanentPlace} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handlePermanentPlaceCreated} />
                </>
            )}
            {activeTab === 7 && (
                <>
                    <SubscriptionCardGrid
                        subscriptions={subscriptions}
                        onSubscriptionRemoved={removeSubscription} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handleSubscriptionCreated} />
                </>
            )}
        </>
    )
}