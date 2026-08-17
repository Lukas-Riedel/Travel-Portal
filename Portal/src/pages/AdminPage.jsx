import { useMemo, useState } from "react"
import { useAuth } from "../contexts/AuthContext"
import TabMenu from "../components/TabMenu"
import TripSummary from "../components/TripSummary"
import { useUpcomingOrCurrentTrip } from "../hooks/useUpcomingOrCurrentTrip"
import ExpenseSummary from "../components/ExpenseSummary"
import { FingerprintPattern, Plus } from "lucide-react"
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
import { useAirports } from "../hooks/useAirports"
import {
    createScheduledFlight, createWatchedFlight, getCoordinates, refreshPlaceAlbum, updateCategoryMetadata,
    listRegularPlaces, createGeographicalExtensionRegion, removeCandidatePlace, logFlight, replaceFitness, updatePlaceCountry,
} from "../clients/coreClient.ts"
import PlaceCardGrid from "../components/PlaceCardGrid"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { useSubscriptions } from "../hooks/useSubscriptions"
import { useCategories } from "../hooks/useCategories"
import SubscriptionCardGrid from "../components/SubscriptionCardGrid"
import RegionEditor from "../components/RegionEditor"
import { useDocuments } from "../hooks/useDocuments"
import DocumentCardGrid from "../components/DocumentCardGrid"
import { useVouchers } from "../hooks/useVouchers"
import VoucherCardGrid from "../components/VoucherCardGrid"
import { useRegions } from "../hooks/useRegions.ts"
import NoteCardGrid from "../components/NoteCardGrid.jsx"
import { useUserInput } from "../hooks/useUserInput.tsx"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { ExpenseCurrency, UserRole } from "../types/CoreSwaggerTypes.ts"
import { useTranslation } from "react-i18next"
import { AdminMenuTabName } from "../types/AdminMenuTabName.ts"
import TaskCardBoard from "../components/TaskCardBoard.tsx"
import { getCurrentTimestamp, getAirportLocalTime } from "../utils/timeUtils.ts"
import { getGeoFeatures, getGeoJson } from "../utils/geocodingUtils.ts"
import { useQueryParamState } from "../hooks/useQueryParamState.ts"

// TODO: Duplicated in CategoryPage. Replace by t(`category.category.${categoryCategory}`).
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

const TAB_URL_QUERY_PARAM_NAME = "tab"
const KEY_URL_QUERY_PARAM_NAME = "key"

export default function AdminPage() {
    const { hasRole, accessToken } = useAuth()
    const { t } = useTranslation()
    const { publishAllAlbumsInvalidatedEvent, publishFolderSynchronizationRequestedEvent } = useEvents()
    const { configuration, updateConfigurationEntry } = useConfiguration()
    const { showCreateAirlineToast, showSynchronizePhotosToast, showCreateSelectedRegionToast, showCreatePlaceToast, showCreateVoucherToast, showCreateDocumentToast, showCreateMultipleGeographicalRegionsToast, showCreateFlightToast, showCreateSubscriptionToast, showCreateTripTaskToast } = usePredefinedUserInput()

    const [selectedKey, setSelectedKey] = useQueryParamState(KEY_URL_QUERY_PARAM_NAME)
    const [selectedTab, setSelectedTab] = useQueryParamState(TAB_URL_QUERY_PARAM_NAME, AdminMenuTabName.Trip)

    const { airlines, createAirline, createAirlineCode, updateAirlineName, updateAirlineLogo, removeAirline, removeAirlineCode } = useAirlines()
    const { updateAirportLongName, updateAirportCountry } = useAirports()
    const devices = useDevices({ type: "agent" })
    const { trips, createTripTask, removeTripTask, updateTripTaskDescription, updateTripTaskPriority } = useRegularTrips({ include: ["watchedFlights", "tasks"] })
    const { trip: upcomingOrCurrentTrip, createTripNote, removeTripNote, createTripExpense, updateTripNoteContent,
        updateTripExpenseDescription, updateTripExpenseValue, removeTripExpense } = useUpcomingOrCurrentTrip()
    const { places: permanentPlaces, createPermanentPlace, removePermanentPlace } = useRegularPlaces({ include: ["categories"], minStart: 0, maxEnd: 0 })
    const { subscriptions, createSubscription, removeSubscription } = useSubscriptions()
    const { documents, createDocument, removeDocument } = useDocuments()
    const { vouchers, createVoucher, updateVoucherValue, removeVoucher } = useVouchers()
    const categories = useCategories()
    const { createGeographicalRegion, createCompositeRegion } = useRegions({ enabled: false })
    const countryCategories = useCategories({ categories: ["country"] })

    const categoriesWithRegions = useMemo(() => categories?.filter(category => category.category !== "country"), [categories])

    const doGetAirportLocalTime = async (airportName, time) => getAirportLocalTime(t("airport.format", { name: airportName }), time)

    const watchedFlights = useMemo(() => {
        const filteredFlights = trips?.flatMap(trip => trip.watchedFlights ?? [])
        return filteredFlights && [...filteredFlights].sort((a, b) => a.start - b.start)
    }, [trips])

    const tasksWithTrips = useMemo(() => trips?.flatMap(trip => (trip.tasks ?? []).map(task => ({ task, trip }))), [trips])

    const tabs = [
        {
            name: AdminMenuTabName.Trip,
            label: t("menu.tab.label.trip"),
            enabled: upcomingOrCurrentTrip !== null && hasRole(UserRole.TripRead) && hasRole(UserRole.PortalFutureRead)
        },
        {
            name: AdminMenuTabName.Flights,
            label: t("menu.tab.label.flights"),
            enabled: hasRole(UserRole.TripFlightRead) && hasRole(UserRole.PortalFutureRead)
        },
        {
            name: AdminMenuTabName.Airlines,
            label: t("menu.tab.label.airlines"),
            enabled: hasRole(UserRole.AirlineEdit)
        },
        {
            name: AdminMenuTabName.DataConsistencyIssues,
            label: t("menu.tab.label.issues"),
            enabled: hasRole(UserRole.MonitoringRead)
        },
        {
            name: AdminMenuTabName.Configuration,
            label: t("menu.tab.label.configuration"),
            enabled: configuration !== null && hasRole(UserRole.ConfigurationEdit)
        },
        {
            name: AdminMenuTabName.Devices,
            label: t("menu.tab.label.devices"),
            enabled: devices && devices.length > 0 && hasRole(UserRole.DeviceRead)
        },
        {
            name: AdminMenuTabName.PermanentPlaces,
            label: t("menu.tab.label.places"),
            enabled: hasRole(UserRole.PlaceEdit)
        },
        {
            name: AdminMenuTabName.ActiveSubscriptions,
            label: t("menu.tab.label.subscriptions"),
            enabled: hasRole(UserRole.SubscriptionEdit)
        },
        {
            name: AdminMenuTabName.Regions,
            label: t("menu.tab.label.regions"),
            enabled: hasRole(UserRole.RegionEdit)
        },
        {
            name: AdminMenuTabName.Documents,
            label: t("menu.tab.label.documents"),
            enabled: hasRole(UserRole.DocumentEdit)
        },
        {
            name: AdminMenuTabName.Vouchers,
            label: t("menu.tab.label.vouchers"),
            enabled: hasRole(UserRole.VoucherEdit)
        },
        {
            name: AdminMenuTabName.Tasks,
            label: t("menu.tab.label.tasks"),
            enabled: hasRole(UserRole.TripTaskEdit)
        }
    ]

    const activeTab = useMemo(() => tabs.map(label => label.name).indexOf(selectedTab), [tabs, selectedTab])

    // TODO: This is temporary to stop fetching data consistency issues every time since there are too many of them right now. The assumption is that there will always be a very little of them otherwise.
    const dataConsistencyIssues = useDataConsistencyIssues(activeTab === 3)

    const handleFlightCreated = () => {
        showCreateFlightToast(async (flight, from, scheduledDeparture, to, scheduledArrival, type) => {
            if (type === "scheduled" || type === "logged") {
                return createScheduledFlight(flight, from, to, await doGetAirportLocalTime(from, scheduledDeparture), await doGetAirportLocalTime(to, scheduledArrival))
            }
            else if (type === "watched") {
                return createWatchedFlight(flight, from, to, await doGetAirportLocalTime(from, scheduledDeparture), await doGetAirportLocalTime(to, scheduledArrival))
            }
            else {
                return Promise.reject(`Unknown flight type '${type}'.`)
            }
        })
    }

    const handleAirlineCreated = () => {
        showCreateAirlineToast(createAirline)
    }

    const handleSubscriptionCreated = () => {
        showCreateSubscriptionToast(async (description, value, currency, expiration) => {
            const convertedExpiration = Math.round(expiration.getTime() / 1000)
            if (convertedExpiration < Date.now() / 1000) {
                return Promise.reject("Expiration must be in the future.")
            }

            return createSubscription(description, value, currency, convertedExpiration)
        })
    }

    const handleDocumentCreated = () => {
        showCreateDocumentToast(async (name, code, issuer, expiration) => {
            if (!expiration) {
                return createDocument(name, code, issuer, undefined)
            }

            const convertedExpiration = Math.round(expiration.getTime() / 1000)
            if (convertedExpiration < Date.now() / 1000) {
                return Promise.reject("Expiration must be in the future.")
            }

            return createDocument(name, code, issuer, convertedExpiration)
        })
    }

    const handleVoucherCreated = () => {
        showCreateVoucherToast((code, issuer, value, currency, expiration) => {
            const convertedExpiration = Math.round(new Date(expiration).getTime() / 1000)
            if (convertedExpiration < Date.now() / 1000) {
                return Promise.reject("Expiration must be in the future.")
            }

            return createVoucher(code, issuer, value, currency, convertedExpiration)
        })
    }

    const handleTaskCreated = () => {
        showCreateTripTaskToast(trips?.filter(trip => trip.end > getCurrentTimestamp()), (tripId, description, priority, deadline) => createTripTask(tripId, description, priority, deadline && (deadline.getTime() / 1000)))
    }

    const handlePermanentPlaceCreated = () => {
        showCreatePlaceToast(createPermanentPlace)
    }

    const handleRegionCreated = () => {
        showCreateSelectedRegionToast(countryCategories, getGeoJson, getGeoFeatures,
            (name, category, geoJson, country, radius) => {
                const geoFeatures = getGeoFeatures(geoJson)
                if (geoFeatures.length !== 1) {
                    return Promise.reject("There must be exactly one feature in the GeoJSON, but there are " + geoFeatures.length + " features.")
                }

                return createGeographicalRegion(name, country, category, radius, geoJson)
            },
            (name, category, includedCategories, excludedCategories) => createCompositeRegion(name, category, includedCategories, excludedCategories))
    }

    const handleFolderSynchronizationRequested = agentId => {
        showSynchronizePhotosToast((path, expiration) => {
            const convertedExpiration = Math.round(expiration.getTime() / 1000)
            if (convertedExpiration < Date.now() / 1000) {
                return Promise.reject("Expiration must be in the future.")
            }

            return publishFolderSynchronizationRequestedEvent(agentId, path, convertedExpiration)
        })
    }

    return tabs.some(label => label.enabled) && (
        <>
            <TabMenu
                tabs={tabs}
                selectedTab={selectedTab}
                onTabSelected={setSelectedTab} />
            {activeTab === 0 && hasRole(UserRole.TripRead) && hasRole(UserRole.PortalFutureRead) && (
                <>
                    <TripSummary
                        trip={upcomingOrCurrentTrip}
                        displayWarnings={hasRole(UserRole.PortalWarningRead)}
                        onNoteAdded={hasRole(UserRole.TripNoteEdit) && createTripNote}
                        onNoteRemoved={hasRole(UserRole.TripNoteEdit) && removeTripNote} />
                    {hasRole(UserRole.TripNoteRead) && (
                        <NoteCardGrid
                            rowSize={3}
                            notes={upcomingOrCurrentTrip && (upcomingOrCurrentTrip.notes ?? [])}
                            onNoteCreated={createTripNote}
                            onNoteContentUpdated={updateTripNoteContent}
                            onNoteRemoved={removeTripNote} />
                    )}
                    {hasRole(UserRole.TripExpenseRead) && (
                        <ExpenseSummary
                            expenses={upcomingOrCurrentTrip && (upcomingOrCurrentTrip.expenses ?? [])}
                            onExpenseCreated={hasRole(UserRole.TripExpenseEdit) && createTripExpense}
                            onExpenseDescriptionUpdated={hasRole(UserRole.TripExpenseEdit) && updateTripExpenseDescription}
                            onExpenseValueUpdated={hasRole(UserRole.TripExpenseEdit) && updateTripExpenseValue}
                            onExpenseRemoved={hasRole(UserRole.TripExpenseEdit) && removeTripExpense} />
                    )}
                </>
            )}
            {activeTab === 1 && hasRole(UserRole.TripFlightRead) && hasRole(UserRole.PortalFutureRead) && (
                <>
                    <FlightCardGrid
                        rowSize={4}
                        flights={watchedFlights} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handleFlightCreated} />
                </>
            )}
            {activeTab === 2 && hasRole(UserRole.AirlineEdit) && (
                <>
                    <AirlineCardGrid
                        rowSize={6}
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
            {activeTab === 3 && hasRole(UserRole.MonitoringRead) && (
                <DataConsistencyIssueCardGrid
                    dataConsistencyIssues={dataConsistencyIssues}
                    airlines={airlines}
                    rowSize={4}
                    onAirlineCodeAssigned={createAirlineCode}
                    onFitnessReplaced={replaceFitness}
                    onAirlineLogoChanged={updateAirlineLogo}
                    onAirportNameChanged={updateAirportLongName}
                    onAllAlbumsInvalidated={publishAllAlbumsInvalidatedEvent}
                    onPhotoInvalidated={photoId => listRegularPlaces({ photoId: photoId, include: ["dates"] })
                        .then(places => Promise.all(places.flatMap(place => place.dates.map(date => refreshPlaceAlbum(place.id, date.album.id)))))}
                    // TODO: Replace by hook methods?
                    onGeographicalExtensionCategoryAdded={createGeographicalExtensionRegion}
                    onPlaceRemoved={removeCandidatePlace}
                    onFlightLogged={logFlight}
                    onCategoryMetadataChanged={updateCategoryMetadata}
                    onPlaceCountryChanged={updatePlaceCountry}
                    onAirportCountryChanged={updateAirportCountry} />
            )}
            {activeTab === 4 && hasRole(UserRole.ConfigurationEdit) && (
                <>
                    <ConfigurationEditor
                        configuration={configuration}
                        onConfigurationUpdated={updateConfigurationEntry}
                        selectedKey={selectedKey}
                        onKeySelected={setSelectedKey} />
                    <form
                        action={(window.env?.VITE_IAM_BASE_URL || import.meta.env.VITE_IAM_BASE_URL) + "/google/auth"}
                        method="post"
                        target="_blank">
                        <input
                            type="hidden"
                            name="token"
                            value={accessToken} />
                        <button
                            type="submit"
                            className="fixed bottom-8 right-8 bg-white hover:bg-gray-100 text-black p-3 rounded-full shadow-md transition-colors duration-200">
                            <FingerprintPattern className="w-6 h-6" />
                        </button>
                    </form>
                </>
            )}
            {activeTab === 5 && hasRole(UserRole.DeviceRead) && (
                <DeviceCardGrid
                    devices={devices}
                    rowSize={4}
                    onFolderSynchronizationRequested={hasRole(UserRole.PlaceAlbumEdit) && handleFolderSynchronizationRequested} />
            )}
            {activeTab === 6 && hasRole(UserRole.PlaceEdit) && (
                <>
                    <PlaceCardGrid
                        places={permanentPlaces}
                        rowSize={5}
                        onPlaceRemoved={removePermanentPlace} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handlePermanentPlaceCreated} />
                </>
            )}
            {activeTab === 7 && hasRole(UserRole.SubscriptionEdit) && (
                <>
                    <SubscriptionCardGrid
                        rowSize={5}
                        subscriptions={subscriptions}
                        onSubscriptionRemoved={removeSubscription} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handleSubscriptionCreated} />
                </>
            )}
            {activeTab === 8 && hasRole(UserRole.RegionEdit) && (
                <>
                    <RegionEditor
                        categories={categoriesWithRegions}
                        selectedKey={selectedKey}
                        onKeySelected={setSelectedKey} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handleRegionCreated} />
                </>
            )}
            {activeTab === 9 && hasRole(UserRole.DocumentEdit) && (
                <>
                    <DocumentCardGrid
                        rowSize={4}
                        documents={documents}
                        onDocumentRemoved={hasRole(UserRole.DocumentEdit) && removeDocument} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handleDocumentCreated} />
                </>
            )}
            {activeTab === 10 && hasRole(UserRole.VoucherEdit) && (
                <>
                    <VoucherCardGrid
                        rowSize={4}
                        vouchers={vouchers}
                        onVoucherValueUpdated={updateVoucherValue}
                        onVoucherRemoved={removeVoucher} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handleVoucherCreated} />
                </>
            )}
            {activeTab === 11 && hasRole(UserRole.TripTaskEdit) && (
                <>
                    <TaskCardBoard
                        rowSize={4}
                        tasksWithTrips={tasksWithTrips}
                        onTaskDescriptionUpdated={updateTripTaskDescription}
                        onTaskPriorityUpdated={updateTripTaskPriority}
                        onTaskRemoved={removeTripTask} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handleTaskCreated} />
                </>
            )}
        </>
    )
}
