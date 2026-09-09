import { FingerprintPattern, Plus } from "lucide-react"
import { useTranslation } from "react-i18next"

import {
createGeographicalExtensionRegion,     createScheduledFlight, createWatchedFlight,     listRegularPlaces, logFlight, refreshPlaceAlbum, removeCandidatePlace, replaceFitness, updateCategoryMetadata,
updatePlaceCountry,
} from "../clients/coreClient.ts"
import AirlineCardGrid from "../components/AirlineCardGrid.tsx"
import ConfigurationEditor from "../components/ConfigurationEditor.tsx"
import DataConsistencyIssueCardGrid from "../components/DataConsistencyIssueCardGrid.tsx"
import DeviceCardGrid from "../components/DeviceCardGrid.tsx"
import DocumentCardGrid from "../components/DocumentCardGrid.tsx"
import ExpenseSummary from "../components/ExpenseSummary.tsx"
import FlightCardGrid from "../components/FlightCardGrid.tsx"
import FloatingButton from "../components/FloatingButton.js"
import NoteCardGrid from "../components/NoteCardGrid.tsx"
import PlaceCardGrid from "../components/PlaceCardGrid.tsx"
import RegionEditor from "../components/RegionEditor.tsx"
import SubscriptionCardGrid from "../components/SubscriptionCardGrid.tsx"
import TabMenu from "../components/TabMenu.tsx"
import TaskCardBoard from "../components/TaskCardBoard.tsx"
import TripSummary from "../components/TripSummary.tsx"
import VoucherCardGrid from "../components/VoucherCardGrid.tsx"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useConfiguration } from "../contexts/ConfigContext.tsx"
import { useAirlines } from "../hooks/useAirlines.ts"
import { useAirports } from "../hooks/useAirports.ts"
import { useCategories } from "../hooks/useCategories.ts"
import { useDataConsistencyIssues } from "../hooks/useDataConsistencyIssues.ts"
import { useDevices } from "../hooks/useDevices.ts"
import { useDocuments } from "../hooks/useDocuments.ts"
import { useEvents } from "../hooks/useEvents.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { useQueryParamState } from "../hooks/useQueryParamState.ts"
import { useRegions } from "../hooks/useRegions.ts"
import { useRegularPlaces } from "../hooks/useRegularPlaces.ts"
import { useRegularTrips } from "../hooks/useRegularTrips.ts"
import { useSubscriptions } from "../hooks/useSubscriptions.ts"
import { useUpcomingOrCurrentTrip } from "../hooks/useUpcomingOrCurrentTrip.ts"
import { useVouchers } from "../hooks/useVouchers.ts"
import { AdminMenuTabName } from "../types/AdminMenuTabName.ts"
import { CategoryCategory, DeviceType, FlightType, PlaceIncludedEntity, TripIncludedEntity, UserRole } from "../types/CoreSwaggerTypes.ts"
import { getGeoFeatures, getGeoJson } from "../utils/geocodingUtils.ts"
import { getGoogleCloudAuthenticationLink } from "../utils/navigationUtils.ts"
import { getAirportLocalTime, getCurrentTimestamp, getNoonTimestamp,isTodayOrFutureDay } from "../utils/timeUtils.ts"

const TAB_URL_QUERY_PARAM_NAME = "tab"
const KEY_URL_QUERY_PARAM_NAME = "key"

export default function AdminPage() {
    const { hasRole, accessToken } = useAuth()
    const { t } = useTranslation()
    const { publishAllAlbumsInvalidatedEvent, publishFolderSynchronizationRequestedEvent, publishPhotosUploadingTriggeredEvent } = useEvents()
    const { configuration, updateConfigurationEntry } = useConfiguration()
    const { showCreateAirlineToast, showSynchronizePhotosToast, showCreateSelectedRegionToast, showCreatePlaceToast, showCreateVoucherToast,
        showCreateDocumentToast, showCreateFlightToast, showCreateSubscriptionToast, showCreateTripTaskToast } = usePredefinedUserInput()

    const [selectedKey, setSelectedKey] = useQueryParamState(KEY_URL_QUERY_PARAM_NAME)
    const [selectedTab, setSelectedTab] = useQueryParamState(TAB_URL_QUERY_PARAM_NAME, AdminMenuTabName.Trip)

    const { airlines, createAirline, createAirlineCode, updateAirlineName, updateAirlineLogo, removeAirline, removeAirlineCode } = useAirlines()
    const { updateAirportLongName, updateAirportCountry } = useAirports()
    const devices = useDevices({ type: DeviceType.Agent })
    const { trips, createTripTask, removeTripTask, updateTripTaskDescription, updateTripTaskPriority } = useRegularTrips({ include: [TripIncludedEntity.WatchedFlights, TripIncludedEntity.Tasks] })
    const { trip: upcomingOrCurrentTrip, createTripNote, removeTripNote, createTripExpense, updateTripNoteContent,
        updateTripExpenseDescription, updateTripExpenseValue, removeTripExpense } = useUpcomingOrCurrentTrip()
    const { places: permanentPlaces, createPermanentPlace, removePermanentPlace } = useRegularPlaces({ include: [PlaceIncludedEntity.Categories], minStart: 0, maxEnd: 0 })
    const { subscriptions, createSubscription, removeSubscription } = useSubscriptions()
    const { documents, createDocument, removeDocument } = useDocuments()
    const { vouchers, createVoucher, updateVoucherValue, removeVoucher } = useVouchers()
    const categories = useCategories()
    const { createGeographicalRegion, createCompositeRegion } = useRegions({ enabled: false })
    const countryCategories = useCategories({ categories: [CategoryCategory.Country] })

    const categoriesWithRegions = categories?.filter(category => category.category !== CategoryCategory.Country)

    const filteredWatchedFlights = trips?.flatMap(trip => trip.watchedFlights ?? [])
    const watchedFlights = filteredWatchedFlights && [...filteredWatchedFlights].sort((a, b) => a.start - b.start)

    const tasksWithTrips = trips?.flatMap(trip => (trip.tasks ?? []).map(task => ({ task, trip })))

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
            enabled: !!(devices && devices.length > 0 && hasRole(UserRole.DeviceRead))
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

    const activeTab = tabs.find(tab => tab.name === selectedTab)?.name

    // TODO: This is temporary to stop fetching data consistency issues every time since there are too many of them right now. The assumption is that there will always be a very little of them otherwise.
    const dataConsistencyIssues = useDataConsistencyIssues(activeTab === AdminMenuTabName.DataConsistencyIssues)

    const doGetAirportLocalTime = async (airportName: string, time: Date) => getAirportLocalTime(t("airport.format", { name: airportName }), time)

    const handleFlightCreated = () => {
        showCreateFlightToast(async (flight, from, scheduledDeparture, to, scheduledArrival, type) => {
            if (type === FlightType.Scheduled || FlightType.Logged) {
                return createScheduledFlight(flight, from, to, await doGetAirportLocalTime(from, scheduledDeparture), await doGetAirportLocalTime(to, scheduledArrival))
            }
            else if (type === FlightType.Watched) {
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
            if (!isTodayOrFutureDay(expiration)) {
                return Promise.reject("Expiration must be in the future.")
            }

            return createSubscription(description, value, currency, getNoonTimestamp(expiration))
        })
    }

    const handleDocumentCreated = () => {
        showCreateDocumentToast(async (name, code, issuer, expiration) => {
            if (!expiration) {
                return createDocument(name, code, issuer, undefined)
            }

            if (!isTodayOrFutureDay(expiration)) {
                return Promise.reject("Expiration must be in the future.")
            }

            return createDocument(name, code, issuer, getNoonTimestamp(expiration))
        })
    }

    const handleVoucherCreated = () => {
        showCreateVoucherToast((code, issuer, value, currency, expiration) => {
            if (!expiration || !isTodayOrFutureDay(expiration)) {
                return Promise.reject("Expiration must be in the future.")
            }

            return createVoucher(code, issuer, value, currency, getNoonTimestamp(expiration))
        })
    }

    const handleTaskCreated = () => {
        showCreateTripTaskToast((trips ?? []).filter(trip => (trip.end ?? 0) > getCurrentTimestamp()), (tripId, description, priority, deadline) => {
            if (!deadline || !isTodayOrFutureDay(deadline)) {
                return Promise.reject("Deadline must be in the future.")
            }

            return createTripTask(tripId, description, priority, getNoonTimestamp(deadline))
        })
    }

    const handlePermanentPlaceCreated = () => {
        showCreatePlaceToast(createPermanentPlace)
    }

    const handleRegionCreated = () => {
        showCreateSelectedRegionToast(countryCategories ?? [], getGeoJson, getGeoFeatures,
            (name, category, geoJson, country, radius) => {
                const geoFeatures = getGeoFeatures(geoJson)
                if (geoFeatures.length !== 1) {
                    return Promise.reject("There must be exactly one feature in the GeoJSON, but there are " + geoFeatures.length + " features.")
                }

                return createGeographicalRegion(name, country, category, radius ?? 0, geoJson)
            },
            (name, category, includedCategories, excludedCategories) => createCompositeRegion(name, category, includedCategories, excludedCategories))
    }

    const handleFolderSynchronizationRequested = (agentId: string) => {
        showSynchronizePhotosToast((path, expiration) => {
            if (!isTodayOrFutureDay(expiration)) {
                return Promise.reject("Expiration must be in the future.")
            }

            return publishFolderSynchronizationRequestedEvent(agentId, path, getNoonTimestamp(expiration))
        })
    }

    return tabs.some(label => label.enabled) && (
        <>
            <TabMenu
                tabs={tabs}
                selectedTab={selectedTab ?? undefined}
                onTabSelected={setSelectedTab} />
            {activeTab === AdminMenuTabName.Trip && hasRole(UserRole.TripRead) && hasRole(UserRole.PortalFutureRead) && (
                <>
                    <TripSummary
                        trip={upcomingOrCurrentTrip ?? null}
                        displayWarnings={hasRole(UserRole.PortalWarningRead)}
                        displayDeviceData={hasRole(UserRole.PortalFutureRead)}
                        onNoteAdded={hasRole(UserRole.TripNoteEdit) ? createTripNote : undefined}
                        onNoteRemoved={hasRole(UserRole.TripNoteEdit) ? removeTripNote : undefined}
                        onPhotosAdded={hasRole(UserRole.PlaceAlbumEdit) ? publishPhotosUploadingTriggeredEvent : undefined} />
                    {hasRole(UserRole.TripNoteRead) && (
                        <NoteCardGrid
                            rowSize={3}
                            notes={upcomingOrCurrentTrip ? (upcomingOrCurrentTrip.notes ?? []) : null}
                            onNoteCreated={createTripNote}
                            onNoteContentUpdated={updateTripNoteContent}
                            onNoteRemoved={removeTripNote} />
                    )}
                    {hasRole(UserRole.TripExpenseRead) && (
                        <ExpenseSummary
                            expenses={upcomingOrCurrentTrip ? (upcomingOrCurrentTrip.expenses ?? []) : null}
                            onExpenseCreated={hasRole(UserRole.TripExpenseEdit) ? createTripExpense : undefined}
                            onExpenseDescriptionUpdated={hasRole(UserRole.TripExpenseEdit) ? updateTripExpenseDescription : undefined}
                            onExpenseValueUpdated={hasRole(UserRole.TripExpenseEdit) ? updateTripExpenseValue : undefined}
                            onExpenseRemoved={hasRole(UserRole.TripExpenseEdit) ? removeTripExpense : undefined} />
                    )}
                </>
            )}
            {activeTab === AdminMenuTabName.Flights && hasRole(UserRole.TripFlightRead) && hasRole(UserRole.PortalFutureRead) && (
                <>
                    <FlightCardGrid
                        rowSize={4}
                        flights={watchedFlights ?? null} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handleFlightCreated} />
                </>
            )}
            {activeTab === AdminMenuTabName.Airlines && hasRole(UserRole.AirlineEdit) && (
                <>
                    <AirlineCardGrid
                        rowSize={6}
                        columnSize={4}
                        airlines={airlines ?? null}
                        onAirlineRemoved={removeAirline}
                        onAirlineNameUpdated={updateAirlineName}
                        onAirlineLogoUpdated={updateAirlineLogo}
                        onAirlineCodeRemoved={removeAirlineCode} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handleAirlineCreated} />
                </>
            )}
            {activeTab === AdminMenuTabName.DataConsistencyIssues && hasRole(UserRole.MonitoringRead) && (
                <DataConsistencyIssueCardGrid
                    dataConsistencyIssues={dataConsistencyIssues ?? null}
                    airlines={airlines ?? null}
                    rowSize={4}
                    columnSize={8}
                    onAirlineCodeAssigned={createAirlineCode}
                    onFitnessReplaced={replaceFitness}
                    onAirlineLogoChanged={updateAirlineLogo}
                    onAirportNameChanged={updateAirportLongName}
                    onAllAlbumsInvalidated={publishAllAlbumsInvalidatedEvent}
                    onPhotoInvalidated={photoId => listRegularPlaces({ photoId: photoId, include: [PlaceIncludedEntity.Dates] })
                        .then(places => (Promise.all(places.flatMap(place => (place.dates ?? []).flatMap(date => date.album ? [refreshPlaceAlbum(place.id, date.album.id)] : []))), undefined))}
                    // TODO: Replace by hook methods? Using methods not defined in hook leads to queries in those hooks not being updated, and UI displaying obsolete data.
                    onGeographicalExtensionCategoryAdded={createGeographicalExtensionRegion}
                    onPlaceRemoved={removeCandidatePlace}
                    onFlightLogged={logFlight}
                    onCategoryMetadataChanged={updateCategoryMetadata}
                    onPlaceCountryChanged={updatePlaceCountry}
                    onAirportCountryChanged={updateAirportCountry} />
            )}
            {activeTab === AdminMenuTabName.Configuration && hasRole(UserRole.ConfigurationEdit) && (
                <>
                    <ConfigurationEditor
                        configuration={configuration ?? null}
                        onConfigurationUpdated={updateConfigurationEntry}
                        selectedKey={selectedKey ?? undefined}
                        onKeySelected={setSelectedKey} />
                    <form
                        action={getGoogleCloudAuthenticationLink()}
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
            {activeTab === AdminMenuTabName.Devices && hasRole(UserRole.DeviceRead) && (
                <DeviceCardGrid
                    devices={devices ?? null}
                    rowSize={4}
                    onFolderSynchronizationRequested={hasRole(UserRole.PlaceAlbumEdit) ? handleFolderSynchronizationRequested : undefined} />
            )}
            {activeTab === AdminMenuTabName.PermanentPlaces && hasRole(UserRole.PlaceEdit) && (
                <>
                    <PlaceCardGrid
                        places={permanentPlaces ?? null}
                        rowSize={5}
                        onPlaceRemoved={removePermanentPlace} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handlePermanentPlaceCreated} />
                </>
            )}
            {activeTab === AdminMenuTabName.ActiveSubscriptions && hasRole(UserRole.SubscriptionEdit) && (
                <>
                    <SubscriptionCardGrid
                        rowSize={5}
                        subscriptions={subscriptions ?? null}
                        onSubscriptionRemoved={removeSubscription} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handleSubscriptionCreated} />
                </>
            )}
            {activeTab === AdminMenuTabName.Regions && hasRole(UserRole.RegionEdit) && (
                <>
                    <RegionEditor
                        categories={categoriesWithRegions ?? null}
                        selectedKey={selectedKey ?? undefined}
                        onKeySelected={setSelectedKey} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handleRegionCreated} />
                </>
            )}
            {activeTab === AdminMenuTabName.Documents && hasRole(UserRole.DocumentEdit) && (
                <>
                    <DocumentCardGrid
                        rowSize={4}
                        documents={documents ?? null}
                        onDocumentRemoved={hasRole(UserRole.DocumentEdit) ? removeDocument : undefined} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handleDocumentCreated} />
                </>
            )}
            {activeTab === AdminMenuTabName.Vouchers && hasRole(UserRole.VoucherEdit) && (
                <>
                    <VoucherCardGrid
                        rowSize={4}
                        vouchers={vouchers ?? null}
                        onVoucherValueUpdated={updateVoucherValue}
                        onVoucherRemoved={removeVoucher} />
                    <FloatingButton
                        icon={Plus}
                        onClick={handleVoucherCreated} />
                </>
            )}
            {activeTab === AdminMenuTabName.Tasks && hasRole(UserRole.TripTaskEdit) && (
                <>
                    <TaskCardBoard
                        tasksWithTrips={tasksWithTrips ?? null}
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
