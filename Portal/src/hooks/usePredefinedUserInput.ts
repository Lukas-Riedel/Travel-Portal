import { useTranslation } from "react-i18next"
import type { UsePredefinedUserInputResult } from "../types/UsePredefinedUserInputResult.ts"
import { useUserInput } from "./useUserInput.tsx"
import type { Airline, Album, Document, Expense, Flight, Highlight, Note, Subscription, Voucher, Place, Trip, Year, Category, Label, Airport, Device, TimeBasedFitness, Fitness, CategoryMetadata, GeographicalRegion } from "../types/CoreSwaggerTypes.ts"
import { format, fromUnixTime } from "date-fns"
import type { Highlightable } from "../types/Highlightable.ts"

export const usePredefinedUserInput = (): UsePredefinedUserInputResult => {
    const { showConfirmToast, showInputToast, showFormToast } = useUserInput()
    const { t } = useTranslation()

    const showUpdateAirportCountryToast = (updateAirportCountry: (country: string) => Promise<Airport>) =>
        showInputToast(
            t("airport.prompt.update.country.message"),
            updateAirportCountry,
            t("airport.prompt.update.country.confirmed"),
            t("airport.prompt.update.country.failed")
        )

    const showUpdateAirportNameToast = (updateAirportName: (name: string) => Promise<Airport>) =>
        showInputToast(
            t("airport.prompt.update.name.message"),
            updateAirportName,
            t("airport.prompt.update.name.confirmed"),
            t("airport.prompt.update.name.failed")
        )

    const showUpdatePlaceCountryToast = (updatePlaceCountry: (country: string) => Promise<Place>) =>
        showInputToast(
            t("place.prompt.update.country.message"),
            updatePlaceCountry,
            t("place.prompt.update.country.confirmed"),
            t("place.prompt.update.country.failed")
        )

    const showLoginToast = (login: (username: string, password: string) => Promise<void>) =>
        showFormToast(
            t("general.prompt.login.message"),
            [
                {
                    type: "string",
                    required: true,
                    label: t("general.prompt.login.label.username")
                },
                {
                    type: "password",
                    required: true,
                    label: t("general.prompt.login.label.password")
                }
            ],
            login,
            t("general.prompt.login.confirmed"),
            t("general.prompt.login.failed")
        )

    const showLogoutToast = (logout: () => Promise<void>) =>
        showConfirmToast(
            t("general.prompt.logout.message"),
            logout,
            t("general.prompt.logout.confirmed"),
            t("general.prompt.logout.failed")
        )

    const showUpdatePlaceLocationToast = (updatePlaceLocation: () => Promise<Place>) =>
        showConfirmToast(
            t("place.prompt.update.location.message"),
            updatePlaceLocation,
            t("place.prompt.update.location.confirmed"),
            t("place.prompt.update.location.failed")
        )

    const showUpdatePlaceReviewedToast = (updatePlaceReviewed: () => Promise<Album[]>) =>
        showConfirmToast(
            t("place.prompt.update.reviewed.message"),
            updatePlaceReviewed,
            t("place.prompt.update.reviewed.confirmed"),
            t("place.prompt.update.reviewed.failed")
        )

    const showRefreshPlaceExcerptToast = (refreshPlaceExcerpt: () => Promise<Place>) =>
        showConfirmToast(
            t("place.prompt.refresh.excerpt.message"),
            refreshPlaceExcerpt,
            t("place.prompt.refresh.excerpt.confirmed"),
            t("place.prompt.refresh.excerpt.failed")
        )

    const showUpdateNoteToast = (updateNote: () => Promise<Note>) =>
        showConfirmToast(
            t("note.prompt.update.message"),
            updateNote,
            t("note.prompt.update.confirmed"),
            t("note.prompt.update.failed")
        )

    const showRemoveDocumentToast = (removeDocument: () => Promise<void>) =>
        showConfirmToast(
            t("document.prompt.remove.message"),
            removeDocument,
            t("document.prompt.remove.confirmed"),
            t("document.prompt.remove.failed")
        )

    const showUpdateConfigurationEntryToast = (updateConfigurationEntry: () => Promise<Record<string, any>>) =>
        showConfirmToast(
            t("configuration.prompt.update.message"),
            updateConfigurationEntry,
            t("configuration.prompt.update.confirmed"),
            t("configuration.prompt.update.failed")
        )

    const showRemovePlaceToast = (placesOrRemovePlace: Place[] | (() => Promise<void>), removePlace?: (placeId: string) => Promise<void>) =>
        Array.isArray(placesOrRemovePlace)
            ? showFormToast(
                t("place.prompt.remove.select.message"),
                [
                    {
                        type: "select",
                        required: true,
                        options: placesOrRemovePlace.map(place => ({
                            id: place.id,
                            name: place.name
                        }))
                    }
                ],
                removePlace,
                t("place.prompt.remove.select.confirmed"),
                t("place.prompt.remove.select.failed")
            ) : showConfirmToast(
                t("place.prompt.remove.given.message"),
                placesOrRemovePlace,
                t("place.prompt.remove.given.confirmed"),
                t("place.prompt.remove.given.failed")
            )

    const showRefreshAlbumToast = (refreshAlbum: () => Promise<Album>) =>
        showConfirmToast(
            t("album.prompt.refresh.selected.message"),
            refreshAlbum,
            t("album.prompt.refresh.selected.confirmed"),
            t("album.prompt.refresh.selected.failed")
        )

    const showRemoveAlbumToast = (removeAlbum: () => Promise<void>) =>
        showConfirmToast(
            t("album.prompt.remove.message"),
            removeAlbum,
            t("album.prompt.remove.confirmed"),
            t("album.prompt.remove.failed")
        )

    const showUpdateAlbumMainPhotoToast = (updateAlbumMainPhoto: () => Promise<Album>) =>
        showConfirmToast(
            t("album.prompt.update.cover.message"),
            updateAlbumMainPhoto,
            t("album.prompt.update.cover.confirmed"),
            t("album.prompt.update.cover.failed")
        )

    const showCreateExpenseToast = (subscriptions: Subscription[], vouchers: Voucher[], createExpense: (subscriptionId?: string) => Promise<Expense>, updateVoucherValue: (voucherId: string, value: number) => Promise<Voucher>, removeVoucher: (voucherId: string) => Promise<void>) =>
        showFormToast(
            t("expense.prompt.create.message"),
            [
                {
                    type: "select",
                    required: false,
                    label: t("expense.prompt.create.label.subscription"),
                    options: subscriptions.map(subscription => ({
                        id: subscription.id,
                        name: t("subscription.format", { description: subscription.description, expiration: format(fromUnixTime(subscription.expiration), t("general.format.date")) })
                    }))
                },
                {
                    type: "select",
                    required: false,
                    label: t("expense.prompt.create.label.voucher"),
                    options: vouchers.map(voucher => ({
                        id: voucher.id,
                        name: voucher.expiration
                            ? t("voucher.format.expirable", { issuer: voucher.issuer, value: voucher.value, currency: voucher.currency, expiration: format(fromUnixTime(voucher.expiration), t("general.format.date")) })
                            : t("voucher.format.nonexpirable", { issuer: voucher.issuer, value: voucher.value, currency: voucher.currency }),
                    }))
                }
            ] as const,
            async (subscriptionId, voucherId) => createExpense(subscriptionId)
                .then(async expense => {
                    if (voucherId) {
                        const voucher = vouchers.find(voucher => voucher.id === voucherId)
                        if (voucher.currency !== expense.currency) {
                            return Promise.reject("The voucher currency (" + voucher.currency + ") must match the expense currency (" + expense.currency + ").")
                        }

                        if (voucher.value <= expense.value) {
                            await removeVoucher(voucherId)
                        }
                        else {
                            await updateVoucherValue(voucherId, voucher.value - expense.value)
                        }
                    }

                    return Promise.resolve(expense)
                }),
            t("expense.prompt.create.confirmed"),
            t("expense.prompt.create.failed")
        )

    const showUpdateExpenseValueToast = (expense: Expense, currencies: string[], updateExpenseValue: (value: number, currency: string) => Promise<Expense>) =>
        showFormToast(
            t("expense.prompt.update.value.message"),
            [
                {
                    type: "number",
                    required: true,
                    label: t("expense.prompt.update.value.label.value"),
                    defaultValue: expense.value,
                    min: 0
                },
                {
                    type: "select",
                    required: true,
                    label: t("expense.prompt.update.value.label.currency"),
                    defaultValue: expense.currency,
                    options: currencies.map(currency => ({
                        id: currency,
                        name: currency
                    }))
                }
            ] as const,
            updateExpenseValue,
            t("expense.prompt.update.value.confirmed"),
            t("expense.prompt.update.value.failed")
        )

    const showUpdateExpenseDescriptionToast = (expense: Expense, updateExpenseDescription: (description: string) => Promise<Expense>) =>
        showInputToast(
            t("expense.prompt.update.description.message"),
            updateExpenseDescription,
            t("expense.prompt.update.description.confirmed"),
            t("expense.prompt.update.description.failed"),
            expense.description
        )

    const showRemoveExpenseToast = (removeExpense: () => Promise<void>) =>
        showConfirmToast(
            t("expense.prompt.remove.message"),
            removeExpense,
            t("expense.prompt.remove.confirmed"),
            t("expense.prompt.remove.failed")
        )

    const showCreateNoteToast = (createNote: (() => Promise<Note>) | ((content: string) => Promise<Note>)) =>
        createNote.length === 0
            ? showConfirmToast(
                t("note.prompt.create.message.confirm"),
                createNote as () => Promise<Note>,
                t("note.prompt.create.confirmed"),
                t("note.prompt.create.failed")
            )
            : showInputToast(
                t("note.prompt.create.message.input"),
                createNote as (content: string) => Promise<Note>,
                t("note.prompt.create.confirmed"),
                t("note.prompt.create.failed")
            )

    const showRemoveNoteToast = (removeNote: () => Promise<void>) =>
        showConfirmToast(
            t("note.prompt.remove.message"),
            removeNote,
            t("note.prompt.remove.confirmed"),
            t("note.prompt.remove.failed")
        )

    const showCreateAirlineToast = (createAirline: (name: string) => Promise<Airline>) =>
        showInputToast(
            t("airline.prompt.create.message"),
            createAirline,
            t("airline.prompt.create.confirmed"),
            t("airline.prompt.create.failed")
        )

    const showUpdateAirlineToast = (airline: Airline, updateAirlineName: (name: string) => Promise<Airline>,
        updateAirlineLogo: (logo: string) => Promise<Airline>, removeAirlineCode: (code: string) => Promise<void>) =>
        showFormToast(
            t("airline.prompt.update.all.message"),
            [
                {
                    type: "text",
                    required: true,
                    label: t("airline.prompt.update.all.label.name"),
                    defaultValue: airline.name,
                },
                {
                    type: "text",
                    required: false,
                    label: t("airline.prompt.update.all.label.logo"),
                    defaultValue: airline.logo
                },
                {
                    type: "select",
                    required: false,
                    label: t("airline.prompt.update.all.label.codes"),
                    defaultValue: airline.codes,
                    multiple: true,
                    options: airline.codes.map(code => ({ id: code, name: code }))
                }
            ] as const,
            async (name, logo, codes) => {
                if (airline.name !== name) {
                    await updateAirlineName(name)
                }

                if (airline.logo !== logo) {
                    await updateAirlineLogo(logo)
                }

                await Promise.all(airline.codes.filter(code => !codes.includes(code)).map(code => removeAirlineCode(code)))
            },
            t("airline.prompt.update.all.confirmed"),
            t("airline.prompt.update.all.failed")
        )

    const showRemoveAirlineToast = (removeAirline: () => Promise<void>) =>
        showConfirmToast(
            t("airline.prompt.remove.message"),
            removeAirline,
            t("airline.prompt.remove.confirmed"),
            t("airline.prompt.remove.failed")
        )

    const showRemovePhotoToast = (removePhoto: () => Promise<void>) =>
        showConfirmToast(
            t("photo.prompt.remove.message"),
            removePhoto,
            t("photo.prompt.remove.confirmed"),
            t("photo.prompt.remove.failed")
        )

    const showLogFlightToast = (logFlight: () => Promise<Flight>) =>
        showConfirmToast(
            t("flight.prompt.log.message"),
            logFlight,
            t("flight.prompt.log.confirmed"),
            t("flight.prompt.log.failed")
        )

    const showCreateHighlightToast = (createHighlight: () => Promise<Highlight>) =>
        showConfirmToast(
            t("highlight.prompt.create.message"),
            createHighlight,
            t("highlight.prompt.create.confirmed"),
            t("highlight.prompt.create.failed")
        )

    const showUpdateMainHighlightToast = <T extends Highlightable>(updateMainHighlight: () => Promise<T>) =>
        showConfirmToast(
            t("highlight.prompt.feature.message"),
            updateMainHighlight,
            t("highlight.prompt.feature.confirmed"),
            t("highlight.prompt.feature.failed")
        )

    const showUpdateHighlightToast = (updateHighlight: () => Promise<Highlight>) =>
        showConfirmToast(
            t("highlight.prompt.update.message"),
            updateHighlight,
            t("highlight.prompt.update.confirmed"),
            t("highlight.prompt.update.failed")
        )

    const showRemoveHighlightToast = (removeHighlight: () => Promise<void>) =>
        showConfirmToast(
            t("highlight.prompt.remove.message"),
            removeHighlight,
            t("highlight.prompt.remove.confirmed"),
            t("highlight.prompt.remove.failed")
        )

    const showAssignLabelToast = (createLabel: () => Promise<Label>) =>
        showConfirmToast(
            t("label.prompt.assign.message"),
            createLabel,
            t("label.prompt.assign.confirmed"),
            t("label.prompt.assign.failed")
        )

    const showUnassignLabelToast = (removeLabel: () => Promise<void>) =>
        showConfirmToast(
            t("label.prompt.unassign.message"),
            removeLabel,
            t("label.prompt.unassign.confirmed"),
            t("label.prompt.unassign.failed")
        )

    const showUpdateAirlineLogoToast = (updateAirlineLogo: (logo: string) => Promise<Airline>) =>
        showInputToast(
            t("airline.prompt.update.logo.message"),
            updateAirlineLogo,
            t("airline.prompt.update.logo.confirmed"),
            t("airline.prompt.update.logo.failed")
        )

    const showCreateLabelToast = (createLabel: (name: string) => Promise<Label>) =>
        showInputToast(
            t("label.prompt.create.message"),
            createLabel,
            t("label.prompt.create.confirmed"),
            t("label.prompt.create.failed")
        )

    const showRemoveEntityToast = (removeEntity: () => Promise<void>) =>
        showConfirmToast(
            t("entity.prompt.remove.message"),
            removeEntity,
            t("entity.prompt.remove.confirmed"),
            t("entity.prompt.remove.failed")
        )

    const showUpdateEntityNameToast = <T>(name: string, updateEntityName: (name: string) => Promise<T>) =>
        showInputToast(
            t("entity.prompt.update.name.message"),
            updateEntityName,
            t("entity.prompt.update.name.confirmed"),
            t("entity.prompt.update.name.failed"),
            name
        )

    const showUpdatePlaceExcerptToast = (place: Place, updatePlaceExcerpt: (excerpt: string) => Promise<Place>) =>
        showInputToast(
            t("place.prompt.update.excerpt.message"),
            updatePlaceExcerpt,
            t("place.prompt.update.excerpt.confirmed"),
            t("place.prompt.update.excerpt.failed"),
            place.excerpt
        )

    const showUpdatePlaceAddressToast = (place: Place, updatePlaceAddress: (address: string) => Promise<Place>) =>
        showInputToast(
            t("place.prompt.update.address.message"),
            updatePlaceAddress,
            t("place.prompt.update.address.confirmed"),
            t("place.prompt.update.address.failed"),
            place.name
        )

    const showCopyRegionGeoJsonToast = (copyRegionGeoJson: () => Promise<void>) =>
        showConfirmToast(
            t("region.prompt.copy.geojson.message"),
            copyRegionGeoJson,
            t("region.prompt.copy.geojson.confirmed"),
            t("region.prompt.copy.geojson.failed")
        )

    const showRemoveSubscriptionToast = (removeSubscription: () => Promise<void>) =>
        showConfirmToast(
            t("subscription.prompt.remove.message"),
            removeSubscription,
            t("subscription.prompt.remove.confirmed"),
            t("subscription.prompt.remove.failed")
        )

    const showRemoveTimeTrackingEventToast = (removeTimeTrackingEvent: () => Promise<void>) =>
        showConfirmToast(
            t("tracker.prompt.remove.message"),
            removeTimeTrackingEvent,
            t("tracker.prompt.remove.confirmed"),
            t("tracker.prompt.remove.failed")
        )

    const showCopyTimeTrackingEventDescriptionToast = (copyTimeTrackingEventDescription: () => Promise<void>) =>
        showConfirmToast(
            t("tracker.prompt.copy.description.message"),
            copyTimeTrackingEventDescription,
            t("tracker.prompt.copy.description.confirmed"),
            t("tracker.prompt.copy.description.failed")
        )

    const showRemoveTripToast = (removeTrip: () => Promise<void>) =>
        showConfirmToast(
            t("trip.prompt.remove.message"),
            removeTrip,
            t("trip.prompt.remove.confirmed"),
            t("trip.prompt.remove.failed")
        )

    const showRemoveVoucherToast = (removeVoucher: () => Promise<void>) =>
        showConfirmToast(
            t("voucher.prompt.remove.message"),
            removeVoucher,
            t("voucher.prompt.remove.confirmed"),
            t("voucher.prompt.remove.failed")
        )

    const showCreateMultipleGeographicalRegionsToast = (createGeographicalRegions: (geoJson: string) => Promise<void>) =>
        showInputToast(
            t("region.prompt.create.multiple.message"),
            createGeographicalRegions
        )

    const showReplacePhotoToast = (agents: Device[], replacePhoto: (path: string, agentId: string) => Promise<void>) =>
        showFormToast(
            t("photo.prompt.replace.message"),
            [
                {
                    type: "string",
                    required: true,
                    label: t("photo.prompt.replace.label.path")
                },
                {
                    type: "select",
                    required: true,
                    label: t("photo.prompt.replace.label.agent"),
                    options: agents.map(agent => ({
                        id: agent.id,
                        name: agent.name
                    }))
                }
            ],
            replacePhoto,
            t("photo.prompt.replace.confirmed"),
            t("photo.prompt.replace.failed")
        )

    const showReplaceFitnessToast = (fitnessRecords: Fitness[], replaceFitness: (fitnessRecordIndex: number) => Promise<Fitness>) =>
        showFormToast(
            t("fitness.prompt.replace.message"),
            [
                {
                    type: "select",
                    required: true,
                    options: Array.from({ length: fitnessRecords.length }, (_, index) => ({
                        id: index,
                        name: `${t("fitness.prompt.replace.label.record")} ${index + 1}`
                    }))
                }
            ],
            selectedFitnessRecordIndex => replaceFitness(Number(selectedFitnessRecordIndex)),
            t("fitness.prompt.replace.confirmed"),
            t("fitness.prompt.replace.failed")
        )

    const showUpdateCategoryMetadataToast = (category: Category, updateMetadata: (metadata: CategoryMetadata) => Promise<Category>) =>
        showFormToast(
            t("category.prompt.update.metadata.message"),
            [
                {
                    type: "string",
                    required: false,
                    label: t("category.prompt.update.metadata.label.color"),
                    defaultValue: category.metadata?.color
                },
                {
                    type: "string",
                    required: false,
                    label: t("category.prompt.update.metadata.label.unicode"),
                    defaultValue: category.metadata?.unicode
                },
                {
                    type: "string",
                    required: false,
                    label: t("category.prompt.update.metadata.label.calendar"),
                    defaultValue: category.metadata?.publicHolidaysCalendar
                }
            ],
            (color, unicode, publicHolidaysCalendar) => updateMetadata({ color, unicode, publicHolidaysCalendar }),
            t("category.prompt.update.metadata.confirmed"),
            t("category.prompt.update.metadata.failed"),
        )

    const showAssignAirlineCodeToast = (airlines: Airline[], assignAirlineCode: (airlineId: string) => Promise<Airline>) =>
        showFormToast(
            t("airline.prompt.assign.code.message"),
            [
                {
                    type: "select",
                    required: true,
                    options: airlines.map(airline => ({
                        id: airline.id,
                        name: airline.name
                    }))
                }
            ],
            assignAirlineCode,
            t("airline.prompt.assign.code.confirmed"),
            t("airline.prompt.assign.code.failed")
        )

    const showAssignCategoryToast = (categories: Category[], assignCategory: (categoryName: string) => Promise<GeographicalRegion>) =>
        showFormToast(
            t("place.prompt.assign.category.message"),
            [
                {
                    type: "select",
                    required: true,
                    options: categories.map(category => ({
                        id: category.name,
                        name: category.name
                    }))
                }
            ],
            assignCategory,
            t("place.prompt.assign.category.confirmed"),
            t("place.prompt.assign.category.failed")
        )

    const showSelectHighlightsToast = (selectHighlights: (count: number) => Promise<void>) =>
        showFormToast(
            t("highlight.prompt.select.message"),
            [
                {
                    type: "number",
                    required: true,
                    min: 1
                }
            ],
            selectHighlights,
            t("highlight.prompt.select.confirmed"),
            t("highlight.prompt.select.failed")
        )

    return {
        showSelectHighlightsToast,
        showAssignCategoryToast,
        showAssignAirlineCodeToast,
        showUpdateCategoryMetadataToast,
        showReplaceFitnessToast,
        showReplacePhotoToast,
        showCreateMultipleGeographicalRegionsToast,
        showRemoveVoucherToast,
        showRemoveTripToast,
        showCopyTimeTrackingEventDescriptionToast,
        showRemoveTimeTrackingEventToast,
        showRemoveSubscriptionToast,
        showCopyRegionGeoJsonToast,
        showUpdatePlaceAddressToast,
        showUpdatePlaceExcerptToast,
        showUpdateEntityNameToast,
        showRemoveEntityToast,
        showCreateLabelToast,
        showUpdateAirlineLogoToast,
        showUpdateAirportCountryToast,
        showUpdateAirportNameToast,
        showUpdatePlaceCountryToast,
        showLoginToast,
        showLogoutToast,
        showUpdatePlaceLocationToast,
        showUpdatePlaceReviewedToast,
        showRefreshPlaceExcerptToast,
        showUpdateNoteToast,
        showRemoveDocumentToast,
        showUpdateConfigurationEntryToast,
        showRemovePlaceToast,
        showRefreshAlbumToast,
        showRemoveAlbumToast,
        showUpdateAlbumMainPhotoToast,
        showCreateExpenseToast,
        showUpdateExpenseValueToast,
        showUpdateExpenseDescriptionToast,
        showRemoveExpenseToast,
        showCreateNoteToast,
        showRemoveNoteToast,
        showCreateAirlineToast,
        showUpdateAirlineToast,
        showRemoveAirlineToast,
        showRemovePhotoToast,
        showLogFlightToast,
        showCreateHighlightToast,
        showUpdateMainHighlightToast,
        showUpdateHighlightToast,
        showRemoveHighlightToast,
        showAssignLabelToast,
        showUnassignLabelToast
    }
}