import { useEffect, useMemo, useState } from "react"
import { useAuth } from "../contexts/AuthContext"
import TabMenu from "../components/TabMenu"
import TripSummary from "../components/TripSummary"
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
    createScheduledFlight, createWatchedFlight, getCoordinates, refreshPlaceAlbum, updateCategoryMetadata,
    listRegularPlaces, createGeographicalExtensionRegion, removeCandidatePlace, logFlight, replaceFitness
} from "../clients/coreClient"
import PlaceCardGrid from "../components/PlaceCardGrid"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { useSubscriptions } from "../hooks/useSubscriptions"
import { useCategories } from "../hooks/useCategories"
import SubscriptionCardGrid from "../components/SubscriptionCardGrid"
import RegionEditor from "../components/RegionEditor"
import showBranchingToast from "../components/BranchingToast"
import { getGeoFeatures, getGeoJson } from "../utils/helpers"
import { useDocuments } from "../hooks/useDocuments"
import DocumentCardGrid from "../components/DocumentCardGrid"
import { useVouchers } from "../hooks/useVouchers"
import VoucherCardGrid from "../components/VoucherCardGrid"
import { useRegions } from "../hooks/useRegions.ts"
import NoteCardGrid from "../components/NoteCardGrid.jsx"

// TODO: Duplicated in ExpenseSummary.
const currencies = ["AED", "AFN", "ALL", "AMD", "ANG", "AOA", "ARS", "AUD", "AWG", "AZN", "BAM", "BBD", "BDT", "BGN", "BHD", "BIF", "BMD", "BND", "BOB", "BRL", "BSD", "BTN", "BWP", "BYN", "BZD", "CAD", "CDF", "CHF", "CLP", "CNY", "COP", "CRC", "CUP", "CVE", "CZK", "DJF", "DKK", "DOP", "DZD", "EGP", "ERN", "ETB", "EUR", "FJD", "FKP", "FOK", "GBP", "GEL", "GGP", "GHS", "GIP", "GMD", "GNF", "GTQ", "GYD", "HKD", "HNL", "HRK", "HTG", "HUF", "IDR", "ILS", "IMP", "INR", "IQD", "IRR", "ISK", "JEP", "JMD", "JOD", "JPY", "KES", "KGS", "KHR", "KID", "KMF", "KRW", "KWD", "KYD", "KZT", "LAK", "LBP", "LKR", "LRD", "LSL", "LYD", "MAD", "MDL", "MGA", "MKD", "MMK", "MNT", "MOP", "MRU", "MUR", "MVR", "MWK", "MXN", "MYR", "MZN", "NAD", "NGN", "NIO", "NOK", "NPR", "NZD", "OMR", "PAB", "PEN", "PGK", "PHP", "PKR", "PLN", "PYG", "QAR", "RON", "RSD", "RUB", "RWF", "SAR", "SBD", "SCR", "SDG", "SEK", "SGD", "SHP", "SLE", "SLL", "SOS", "SRD", "SSP", "STN", "SYP", "SZL", "THB", "TJS", "TMT", "TND", "TOP", "TRY", "TTD", "TVD", "TWD", "TZS", "UAH", "UGX", "USD", "UYU", "UZS", "VES", "VND", "VUV", "WST", "XAF", "XCD", "XDR", "XOF", "XPF", "YER", "ZAR", "ZMW", "ZWL"]

// TODO: Duplicated in CategoryPage.
const categoryCategories = {
    continent: "Kontinent",
    // country: "Stát", Except this.
    administrative: "Administrativní oblast",
    ocean: "Oceán",
    sea: "Moře",
    bay: "Záliv",
    island: "Ostrov",
    region: "Geografický region"
}

export default function AdminPage() {
    const { isAdmin } = useAuth()
    const { publishAllAlbumsInvalidatedEvent, publishFolderSynchronizationRequestedEvent } = useEvents()
    const { configuration, updateConfigurationEntry } = useConfiguration()

    const dataConsistencyIssues = useDataConsistencyIssues()
    const { airlines, createAirline, createAirlineCode, updateAirlineName, updateAirlineLogo, removeAirline, removeAirlineCode } = useAirlines()
    const { updateAirportLongName } = useAirports()
    const devices = useDevices({ type: "agent" })
    const trips = useRegularTrips({ include: ["watchedFlights"] })
    const { trip: upcomingOrCurrentTrip, createTripNote, removeTripNote, createTripExpense, updateTripNoteContent,
        updateTripExpenseDescription, updateTripExpenseValue, removeTripExpense } = useUpcomingOrCurrentTrip()
    const { places: permanentPlaces, createPermanentPlace, removePermanentPlace } = useRegularPlaces({ include: ["categories"], minStart: 0, maxEnd: 0 })
    const { subscriptions, createSubscription, removeSubscription } = useSubscriptions()
    const { documents, createDocument, removeDocument } = useDocuments()
    const { vouchers, createVoucher, updateVoucherValue, removeVoucher } = useVouchers()
    const categories = useCategories()
    const { createGeographicalRegion, createCompositeRegion } = useRegions()
    const countryCategories = useCategories({ categories: ["country"] })

    const categoriesWithRegions = useMemo(() => categories?.filter(category => category.category !== "country"), [categories])

    const getAirportTimezone = async (airportName) => (await getCoordinates("Letiště " + airportName))?.timezone
    const getAirportLocalTime = async (airportName, time) => Math.round(fromZonedTime(time, await getAirportTimezone(airportName))?.getTime() / 1000)

    const [activeTab, setActiveTab] = useState(0)

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
            enabled: true
        },
        {
            name: "Aerolinky",
            enabled: true
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
            enabled: true
        },
        {
            name: "Aktivní předplatná",
            enabled: true
        },
        {
            name: "Regiony",
            enabled: true
        },
        {
            name: "Dokumenty",
            enabled: true
        },
        {
            name: "Poukazy",
            enabled: true
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
            async (description, value, currency, expiration) => {
                const convertedExpiration = Math.round(new Date(expiration).getTime() / 1000)
                if (convertedExpiration < Date.now() / 1000) {
                    return Promise.reject("Expiration must be in the future.")
                }

                return createSubscription(description, value, currency, convertedExpiration)
            }
        )
    }

    const handleDocumentCreated = () => {
        showFormToast(
            "Zadej údaje o dokumentu k přidání:",
            [
                { label: "Název", required: true },
                { label: "Identifikátor", required: true },
                { label: "Vydavatel", required: true },
                { label: "Expirace", type: "date" }
            ],
            "Dokument byl úspěšně přidán",
            "Při přidávání dokumentu došlo k chybě",
            async (name, code, issuer, expiration) => {
                const convertedExpiration = Math.round(new Date(expiration).getTime() / 1000)
                if (convertedExpiration < Date.now() / 1000) {
                    return Promise.reject("Expiration must be in the future.")
                }

                return createDocument(name, code, issuer, convertedExpiration)
            }
        )
    }

    const handleVoucherCreated = () => {
        showFormToast(
            "Zadej údaje o poukazu k přidání:",
            [
                { label: "Identifikátor", required: true },
                { label: "Vydavatel", required: true },
                { label: "Hodnota", required: true, type: "number", min: 0 },
                { label: "Měna", required: true, type: "select", options: currencies.map(currency => ({ id: currency, name: currency })) },
                { label: "Expirace", type: "date" }
            ],
            "Poukaz byl úspěšně přidán",
            "Při přidávání poukazu došlo k chybě",
            async (code, issuer, value, currency, expiration) => {
                const convertedExpiration = Math.round(new Date(expiration).getTime() / 1000)
                if (convertedExpiration < Date.now() / 1000) {
                    return Promise.reject("Expiration must be in the future.")
                }

                return createVoucher(code, issuer, value, currency, convertedExpiration)
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

    const handleRegionCreated = () => {
        showBranchingToast(
            "Vyber typ regionu k přidání:",
            {
                geographical: {
                    name: "Geografický",
                    handle: () => showFormToast(
                        "Zadej reprezentaci geografického regionu:",
                        [
                            { label: "Název", required: true },
                            { label: "Stát", required: false, type: "select", options: [{ id: null, name: "" }, ...countryCategories.map(countryCategory => ({ id: countryCategory.name, name: countryCategory.name }))] },
                            { label: "Kategorie", required: true, type: "select", options: Object.keys(categoryCategories).map(categoryCategory => ({ id: categoryCategory, name: categoryCategories[categoryCategory] })) },
                            { label: "Rádius", value: 0, required: true, type: "number", min: 0 },
                            { label: "GeoJSON", required: true }
                        ],
                        "Geografický region byl úspěšně přidán",
                        "Nepodařilo se přidat geografický region",
                        async (name, country, category, radius, geoJson) => {
                            const geoFeatures = getGeoFeatures(JSON.parse(geoJson))
                            if (geoFeatures.length !== 1) {
                                return Promise.reject("There must be exactly one feature in the GeoJSON, but there are " + geoFeatures.length + " features.")
                            }

                            return createGeographicalRegion(name, country, category, radius, getGeoJson(geoFeatures[0].geometry))
                        }
                    )
                },
                composite: {
                    name: "Kompozitní",
                    handle: () =>
                        showFormToast(
                            "Zadej reprezentaci kompozitního regionu:",
                            [
                                { label: "Název", required: true },
                                { label: "Kategorie", required: true, type: "select", options: Object.keys(categoryCategories).map(categoryCategory => ({ id: categoryCategory, name: categoryCategories[categoryCategory] })) },
                                { label: "Zahrnuté regiony", required: true },
                                { label: "Vyloučené regiony", required: false }
                            ],
                            "Kompozitní region byl úspěšně přidán",
                            "Nepodařilo se přidat kompozitní region",
                            async (name, category, includedCategories, excludedCategories) => createCompositeRegion(name, category,
                                includedCategories.split(",").map(name => name.trim()), excludedCategories?.trim() && excludedCategories.split(",").map(name => name.trim()))
                        )
                },
                multipleGeographical: {
                    name: "Multiregion",
                    handle: () => {
                        showInputToast(
                            "Zadej reprezentaci geografických regionů",
                            "",
                            undefined,
                            undefined,
                            async geoJson => {
                                const geoFeatures = getGeoFeatures(JSON.parse(geoJson))
                                for (const geoFeature of geoFeatures) {
                                    try {
                                        await showFormToast(
                                            "Zadej reprezentaci geografického regionu:",
                                            [
                                                { label: "Název", value: Object.keys(geoFeature.properties).map(property => property + " - " + geoFeature.properties[property]), required: true },
                                                // TODO: Use the value from the previous toast as a default.
                                                { label: "Stát", required: false, type: "select", options: [{ id: null, name: "" }, ...countryCategories.map(countryCategory => ({ id: countryCategory.name, name: countryCategory.name }))] },
                                                // TODO: Use the value from the previous toast as a default.
                                                { label: "Kategorie", required: true, type: "select", options: Object.keys(categoryCategories).map(categoryCategory => ({ id: categoryCategory, name: categoryCategories[categoryCategory] })) },
                                                // TODO: Use the value from the previous toast as a default.
                                                { label: "Rádius", value: 0, required: true, type: "number", min: 0 }
                                            ],
                                            "Geografický region byl úspěšně přidán",
                                            "Nepodařilo se přidat geografický region",
                                            async (name, country, category, radius) => createGeographicalRegion(name, country, category, radius, getGeoJson(geoFeature.geometry))
                                        )

                                    }
                                    catch (error) {
                                        continue
                                    }
                                }
                            }
                        )
                    }
                }
            }
        )
    }

    const handleFolderSynchronizationRequested = agentId => {
        showFormToast(
            "Zadej cestu ke složce k automatické synchronizaci:",
            [
                { label: "Cesta", required: true },
                { label: "Konec synchronizace", required: true, type: "datetime-local" },
            ],
            "Automatická synchronizace složky bude brzy zahájena",
            "Při nastavování automatické synchronizace složky došlo k chybě",
            async (path, expiration) => {
                const convertedExpiration = Math.round(new Date(expiration).getTime() / 1000)
                if (convertedExpiration < Date.now() / 1000) {
                    return Promise.reject("Expiration must be in the future.")
                }

                return publishFolderSynchronizationRequestedEvent(agentId, path, convertedExpiration)
            }
        )
    }

    return isAdmin && (
        <>
            <TabMenu
                labels={labels}
                onActiveTabChanged={setActiveTab} />
            {activeTab === 0 && (
                <>
                    <TripSummary
                        trip={upcomingOrCurrentTrip}
                        onNoteAdded={createTripNote}
                        onNoteRemoved={removeTripNote}
                    />
                    <NoteCardGrid
                        notes={upcomingOrCurrentTrip && (upcomingOrCurrentTrip.notes ?? [])}
                        onNoteCreated={createTripNote}
                        onNoteContentUpdated={updateTripNoteContent}
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
                    onCategoryMetadataChanged={updateCategoryMetadata} />
            )}
            {activeTab === 4 && (
                <ConfigurationEditor
                    configuration={configuration}
                    onConfigurationUpdated={updateConfigurationEntry} />
            )}
            {activeTab === 5 && (
                <DeviceCardGrid
                    devices={devices}
                    onFolderSynchronizationRequested={handleFolderSynchronizationRequested} />
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
            {activeTab === 8 && (
                <>
                    <RegionEditor
                        categories={categoriesWithRegions} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handleRegionCreated} />
                </>
            )}
            {activeTab === 9 && (
                <>
                    <DocumentCardGrid
                        documents={documents}
                        onDocumentRemoved={removeDocument} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handleDocumentCreated} />
                </>
            )}
            {activeTab === 10 && (
                <>
                    <VoucherCardGrid
                        vouchers={vouchers}
                        onVoucherValueUpdated={updateVoucherValue}
                        onVoucherRemoved={removeVoucher} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handleVoucherCreated} />
                </>
            )}
        </>
    )
}