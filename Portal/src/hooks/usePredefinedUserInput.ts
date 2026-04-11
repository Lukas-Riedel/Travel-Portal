import { useTranslation } from "react-i18next"
import type { UsePredefinedUserInputResult } from "../types/UsePredefinedUserInputResult.ts"
import { useUserInput } from "./useUserInput.tsx"
import { type Airline, type Album, type Document, type Expense, type Flight, type Highlight, type Note, type Subscription, type Voucher, type Place, type Trip, type Year, type Category, type Label, type Airport, type Device, type TimeBasedFitness, type Fitness, type CategoryMetadata, type GeographicalRegion, type HighlightAttributes, type CompositeRegion, CategoryCategory, type TimeTrackingEvent, TimeTrackingEventType, FlightType } from "../types/CoreSwaggerTypes.ts"
import { format, fromUnixTime } from "date-fns"
import type { Highlightable } from "../types/Highlightable.ts"
import { formatTimestamp } from "../utils/timeUtils.ts"
import { useConfiguration } from "../contexts/ConfigContext.tsx"

export const usePredefinedUserInput = (): UsePredefinedUserInputResult => {
    const { showConfirmToast, showInputToast, showFormToast, showBranchingToast } = useUserInput()
    const { configuration } = useConfiguration()
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
                    type: "text",
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
                        name: t("subscription.format", { description: subscription.description, expiration: format(fromUnixTime(subscription.expiration), t("general.format.date.year.included")) })
                    }))
                },
                {
                    type: "select",
                    required: false,
                    label: t("expense.prompt.create.label.voucher"),
                    options: vouchers.map(voucher => ({
                        id: voucher.id,
                        name: voucher.expiration
                            ? t("voucher.format.expirable", { issuer: voucher.issuer, value: voucher.value, currency: voucher.currency, expiration: format(fromUnixTime(voucher.expiration), t("general.format.date.year.included")) })
                            : t("voucher.format.nonexpirable", { issuer: voucher.issuer, value: voucher.value, currency: voucher.currency }),
                    }))
                }
            ],
            (subscriptionId, voucherId) => createExpense(subscriptionId)
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
            ],
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
                    options: airline.codes.map(code => ({
                        id: code,
                        name: code
                    }))
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
            t("highlight.prompt.adjust.message"),
            updateHighlight,
            t("highlight.prompt.adjust.confirmed"),
            t("highlight.prompt.adjust.failed")
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

    const showCreateMultipleGeographicalRegionsToast = (createGeographicalRegions: (geoJson: object) => Promise<void>) =>
        showInputToast(
            t("region.prompt.create.multiple.message"),
            (geoJson: string) => createGeographicalRegions(JSON.parse(geoJson)),
        )

    const showReplacePhotoToast = (agents: Device[], replacePhoto: (path: string, agentId: string) => Promise<void>) =>
        showFormToast(
            t("photo.prompt.replace.message"),
            [
                {
                    type: "text",
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
                        name: t("fitness.prompt.replace.label.record", { index: index + 1 })
                    }))
                }
            ],
            selectedFitnessRecordIndex => replaceFitness(Number(selectedFitnessRecordIndex)),
            t("fitness.prompt.replace.confirmed"),
            t("fitness.prompt.replace.failed")
        )

    const showUpdateCategoryToast = (category: Category, updateMetadata: (metadata: CategoryMetadata) => Promise<Category>, updateCategory?: (category: CategoryCategory) => Promise<Category>) =>
        showFormToast(
            t("category.prompt.update.message"),
            [
                {
                    type: "text",
                    required: false,
                    label: t("category.prompt.update.label.color"),
                    defaultValue: category.metadata?.color
                },
                {
                    type: "text",
                    required: false,
                    label: t("category.prompt.update.label.unicode"),
                    defaultValue: category.metadata?.unicode
                },
                {
                    type: "text",
                    required: false,
                    label: t("category.prompt.update.label.calendar"),
                    defaultValue: category.metadata?.publicHolidaysCalendar
                },
                updateCategory && {
                    type: "select",
                    required: true,
                    label: t("category.prompt.update.label.category"),
                    options: Object.values(CategoryCategory).map(categoryCategory => ({
                        id: categoryCategory,
                        name: t(`category.category.${categoryCategory}`)
                    }))
                },
            ],
            (color, unicode, publicHolidaysCalendar, category) => {
                const promise = updateMetadata({ color, unicode, publicHolidaysCalendar })
                return updateCategory ? promise.then(() => updateCategory(category as CategoryCategory)) : promise
            },
            t("category.prompt.update.confirmed"),
            t("category.prompt.update.failed"),
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

    const showUploadPhotosToast = (agents: Device[], uploadPhotos: ((path: string, agentId: string, mainPhotoPosition?: number) => Promise<void>) | ((date: string, path: string, agentId: string, mainPhotoPosition?: number) => Promise<void>)) =>
        uploadPhotos.length === 3
            ? showFormToast(
                t("photo.prompt.upload.message"),
                [
                    {
                        type: "text",
                        label: t("photo.prompt.upload.label.path"),
                        required: true
                    },
                    {
                        type: "select",
                        required: true,
                        label: t("photo.prompt.upload.label.agent"),
                        options: agents.map(agent => ({
                            id: agent.id,
                            name: agent.name
                        }))
                    },
                    {
                        type: "number",
                        label: t("photo.prompt.upload.label.position"),
                        required: false,
                        min: 1
                    }
                ],
                (path, agentId, mainPhotoPosition) => (uploadPhotos as (path: string, agentId: string, mainPhotoPosition?: number) => Promise<void>)(path, agentId, mainPhotoPosition && Number(mainPhotoPosition)),
                t("photo.prompt.upload.confirmed"),
                t("photo.prompt.upload.failed")
            )
            : showFormToast(
                t("photo.prompt.upload.message"),
                [
                    {
                        type: "date",
                        label: t("photo.prompt.upload.label.date"),
                        required: true
                    },
                    {
                        type: "text",
                        label: t("photo.prompt.upload.label.path"),
                        required: true
                    },
                    {
                        type: "select",
                        required: true,
                        label: t("photo.prompt.upload.label.agent"),
                        options: agents.map(agent => ({
                            id: agent.id,
                            name: agent.name
                        }))
                    },
                    {
                        type: "number",
                        label: t("photo.prompt.upload.label.position"),
                        required: false,
                        min: 1
                    }
                ],
                (date, path, agentId, mainPhotoPosition) => (uploadPhotos as (date: string, path: string, agentId: string, mainPhotoPosition?: number) => Promise<void>)(date, path, agentId, mainPhotoPosition && Number(mainPhotoPosition)),
                t("photo.prompt.upload.confirmed"),
                t("photo.prompt.upload.failed")
            )

    const showUpdateHighlightAttributesToast = (updateHighlightAttributes: (composition: number | null, sky: number | null, shadows: number | null, circumstances: number | null, atmosphere: number | null) => Promise<Highlight>, highlightAttributes?: HighlightAttributes, timestamp?: number, timezone?: string, sunAltitude?: number) =>
        showFormToast(
            t("highlight.prompt.update.attribute.message"),
            [
                {
                    type: "select",
                    label: t("highlight.prompt.update.attribute.label.composition"),
                    defaultValue: highlightAttributes?.composition,
                    required: true,
                    options: configuration?.highlights?.attribute?.composition?.map(({ id, value }) => ({
                        id: value,
                        name: t(`highlight.prompt.update.attribute.option.composition.${id}`)
                    }))
                },
                {
                    type: "select",
                    label: t("highlight.prompt.update.attribute.label.sky"),
                    defaultValue: highlightAttributes?.sky,
                    required: false,
                    options: configuration?.highlights?.attribute?.sky?.map(({ id, value }) => ({
                        id: value,
                        name: t(`highlight.prompt.update.attribute.option.sky.${id}`)
                    }))
                },
                {
                    type: "select",
                    label: t("highlight.prompt.update.attribute.label.shadows"),
                    defaultValue: highlightAttributes?.shadows,
                    required: false,
                    options: configuration?.highlights?.attribute?.shadows?.map(({ id, value }) => ({
                        id: value,
                        name: t(`highlight.prompt.update.attribute.option.shadows.${id}`)
                    }))
                },
                {
                    type: "select",
                    label: t("highlight.prompt.update.attribute.label.circumstances"),
                    defaultValue: highlightAttributes?.circumstances,
                    required: true,
                    options: configuration?.highlights?.attribute?.circumstances?.map(({ id, value }) => ({
                        id: value,
                        name: t(`highlight.prompt.update.attribute.option.circumstances.${id}`)
                    }))
                },
                {
                    type: "select",
                    label: t("highlight.prompt.update.attribute.label.atmosphere"),
                    defaultValue: highlightAttributes?.atmosphere,
                    required: true,
                    options: configuration?.highlights?.attribute?.atmosphere?.map(({ id, value }) => ({
                        id: value,
                        name: t(`highlight.prompt.update.attribute.option.atmosphere.${id}`)
                    }))
                },
                timestamp && {
                    type: "text",
                    label: t("highlight.prompt.update.attribute.label.datetime"),
                    required: false,
                    disabled: true,
                    defaultValue: formatTimestamp(timestamp, t("general.format.datetime.year.included"), timezone),
                },
                sunAltitude && {
                    type: "text",
                    label: t("highlight.prompt.update.attribute.label.sun"),
                    required: false,
                    disabled: true,
                    defaultValue: `${sunAltitude.toFixed(1)}°`,
                }
            ],
            (composition, sky, shadows, circumstantes, atmosphere) => updateHighlightAttributes(
                composition !== "" ? Number(composition) : null,
                sky !== "" ? Number(sky) : null,
                shadows !== "" ? Number(shadows) : null,
                circumstantes !== "" ? Number(circumstantes) : null,
                atmosphere !== "" ? Number(atmosphere) : null
            ),
            t("highlight.prompt.update.attribute.confirmed"),
            t("highlight.prompt.update.attribute.failed")
        )

    const showOverwriteGeographicalRegionToast = (region: GeographicalRegion, overwriteGeographicalRegion: (radius: number, geoJson: object) => Promise<GeographicalRegion>) =>
        showFormToast(
            t("region.prompt.overwrite.geographical.message"),
            [
                {
                    type: "number",
                    label: t("region.prompt.overwrite.geographical.label.radius"),
                    defaultValue: region.radius,
                    required: true,
                    min: 0
                },
                {
                    type: "text",
                    label: t("region.prompt.overwrite.geographical.label.geojson"),
                    defaultValue: JSON.stringify(region.geoJson),
                    required: true
                }
            ],
            (radius, geoJson) => overwriteGeographicalRegion(Number(radius), JSON.parse(String(geoJson))),
            t("region.prompt.overwrite.geographical.confirmed"),
            t("region.prompt.overwrite.geographical.failed")
        )

    const showOverwriteCompositeRegionToast = (region: CompositeRegion, overwriteCompositeRegion: (includedCategoryNames: string[], excludedCategoryNames: string[]) => Promise<CompositeRegion>) =>
        showFormToast(
            t("region.prompt.overwrite.composite.message"),
            [
                {
                    type: "text",
                    label: t("region.prompt.overwrite.composite.label.included"),
                    defaultValue: region.includedCategories.map(category => category.name).join(","),
                    required: true
                },
                {
                    type: "text",
                    label: t("region.prompt.overwrite.composite.label.included"),
                    defaultValue: region.excludedCategories?.map(category => category.name).join(","),
                    required: false
                }
            ],
            (includedCategoryNames, excludedCategoryNames) => overwriteCompositeRegion(includedCategoryNames.split(",").map(name => name.trim()).filter(Boolean), excludedCategoryNames?.split(",")?.map(name => name.trim())?.filter(Boolean)),
            t("region.prompt.overwrite.composite.confirmed"),
            t("region.prompt.overwrite.composite.failed")
        )

    const showSubtractVoucherValueToast = (subtractVoucherValue: (value: number) => Promise<Voucher>) =>
        showFormToast(
            t("voucher.prompt.subtract.message"),
            [
                {
                    type: "number",
                    required: true,
                    defaultValue: 0,
                }
            ],
            subtractVoucherValue,
            t("voucher.prompt.subtract.confirmed"),
            t("voucher.prompt.subtract.failed")
        )

    const showCreatePlaceToast = (createPlace: (name: string, address: string) => Promise<Place>) =>
        showFormToast(
            t("place.prompt.create.message"),
            [
                {
                    type: "text",
                    label: t("place.prompt.create.label.name"),
                    required: true
                },
                {
                    type: "text",
                    label: t("place.prompt.create.label.address"),
                    required: false
                }
            ],
            (name: string, address: string) => createPlace(name, address || name),
            t("place.prompt.create.confirmed"),
            t("place.prompt.create.failed")
        )

    const showMoveTripToast = (moveTrip: (date: Date) => Promise<Trip>) =>
        showFormToast(
            t("trip.prompt.move.message"),
            [
                {
                    type: "date",
                    required: true
                }
            ],
            (date: string) => moveTrip(new Date(date)),
            t("trip.prompt.move.confirmed"),
            t("trip.prompt.move.failed")
        )

    const showLoadTripToast = (tripCandidates: Trip[], loadTrip: (tripId: string) => Promise<Trip>) =>
        showFormToast(
            t("trip.prompt.load.message"),
            [
                {
                    type: "select",
                    required: true,
                    options: tripCandidates.map(candidateTrip => ({
                        id: candidateTrip.id,
                        name: candidateTrip.name
                    }))
                }
            ],
            loadTrip,
            t("trip.prompt.load.confirmed"),
            t("trip.prompt.load.failed")
        )

    const showCreateOvertimeToast = (defaultOvertimeHours: number, createOvertime: (description: string, hours: number) => Promise<TimeTrackingEvent>) =>
        showFormToast(
            t("tracker.prompt.create.positive.overtime.message"),
            [
                {
                    type: "text",
                    label: t("tracker.prompt.create.positive.overtime.label.description"),
                    required: true
                },
                {
                    type: "number",
                    label: t("tracker.prompt.create.positive.overtime.label.hours"),
                    defaultValue: defaultOvertimeHours,
                    required: true,
                    min: 0
                }
            ] as const,
            (description: string, hours: number) => createOvertime(description, hours),
            t("tracker.prompt.create.positive.overtime.confirmed"),
            t("tracker.prompt.create.positive.overtime.failed")
        )

    const showCreatePlannedWorkToast = (defaultPlannedWorkHours: number, createPlannedWork: (hours: number) => Promise<TimeTrackingEvent>) =>
        showFormToast(
            t("tracker.prompt.create.positive.plannedWork.message"),
            [{
                type: "number",
                defaultValue: defaultPlannedWorkHours,
                required: true
            }],
            createPlannedWork,
            t("tracker.prompt.create.positive.plannedWork.confirmed"),
            t("tracker.prompt.create.positive.plannedWork.failed")
        )

    const showCreateNegativeTimeTrackingEventToast = (type: TimeTrackingEventType, defaultHours: number, createNegativeTimeTrackingEvent: (hours: number) => Promise<TimeTrackingEvent>) =>
        showFormToast(
            t(`tracker.prompt.create.negative.${type}.message`),
            [{
                type: "number",
                defaultValue: defaultHours,
                required: true
            }],
            createNegativeTimeTrackingEvent,
            t(`tracker.prompt.create.negative.${type}.confirmed`),
            t(`tracker.prompt.create.negative.${type}.failed`)
        )

    const showCreateFlightToast = (createFlight: (flight: string, from: string, scheduledDeparture: Date, to: string, scheduledArrival: Date, type: FlightType) => Promise<Flight>) =>
        showFormToast(
            t("flight.prompt.create.message"),
            [
                {
                    type: "text",
                    label: t("flight.prompt.create.label.flight"),
                    required: true,
                    placeholder: "EK139"
                },
                {
                    type: "text",
                    label: t("flight.prompt.create.label.from"),
                    required: true,
                    placeholder: "Frankurt"
                },
                {
                    type: "datetime-local",
                    label: t("flight.prompt.create.label.departure"),
                    required: true
                },
                {
                    type: "text",
                    label: t("flight.prompt.create.label.to"),
                    required: true,
                    placeholder: "Toronto"
                },
                {
                    type: "datetime-local",
                    label: t("flight.prompt.create.label.arrival"),
                    required: true
                },
                {
                    type: "select",
                    label: "Typ",
                    required: true,
                    options: Object.values(FlightType).map(flightType => ({
                        id: flightType,
                        name: t(`flight.type.${flightType}`)
                    }))
                }
            ],
            (flight, from, scheduledDeparture, to, scheduledArrival, type) => createFlight(flight, from, new Date(scheduledDeparture), to, new Date(scheduledArrival), type as FlightType),
            t("flight.prompt.create.confirmed"),
            t("flight.prompt.create.failed")
        )

    const showCreateSubscriptionToast = (currencies: string[], createSubscription: (description: string, value: number, currency: string, expiration: Date) => Promise<Subscription>) =>
        showFormToast(
            t("subscription.prompt.create.message"),
            [
                {
                    type: "text",
                    label: t("subscription.prompt.create.label.description"),
                    required: true
                },
                {
                    type: "number",
                    label: t("subscription.prompt.create.label.value"),
                    required: true,
                    min: 0
                },
                {
                    type: "select",
                    label: t("subscription.prompt.create.label.currency"),
                    required: true,
                    options: currencies.map(currency => ({
                        id: currency,
                        name: currency
                    }))
                },
                {
                    type: "datetime-local",
                    label: t("subscription.prompt.create.label.expiration"),
                    required: true
                }
            ] as const,
            (description: string, value: number, currency: string, expiration: string) => createSubscription(description, value, currency, new Date(expiration)),
            t("subscription.prompt.create.confirmed"),
            t("subscription.prompt.create.failed")
        )

    const showCreateDocumentToast = (createDocument: (name: string, identifier: string, issuer: string, expiration?: Date) => Promise<Document>) =>
        showFormToast(
            t("document.prompt.create.message"),
            [
                {
                    type: "text",
                    label: t("document.prompt.create.label.name"),
                    required: true
                },
                {
                    type: "text",
                    label: t("document.prompt.create.label.identifier"),
                    required: true
                },
                {
                    type: "text",
                    label: t("document.prompt.create.label.issuer"),
                    required: true
                },
                {
                    type: "date",
                    label: t("document.prompt.create.label.expiration"),
                    required: false
                }
            ] as const,
            (name: string, identifier: string, issuer: string, expiration?: string) => createDocument(name, identifier, issuer, expiration && new Date(expiration)),
            t("document.prompt.create.confirmed"),
            t("document.prompt.create.failed")
        )

    const showCreateVoucherToast = (currencies: string[], createVoucher: (identifier: string, issuer: string, value: number, currency: string, expiration?: Date) => Promise<Voucher>) =>
        showFormToast(
            t("voucher.prompt.create.message"),
            [
                {
                    type: "text",
                    label: t("voucher.prompt.create.label.identifier"),
                    required: true
                },
                {
                    type: "text",
                    label: t("voucher.prompt.create.label.issuer"),
                    required: true
                },
                {
                    type: "number",
                    label: t("voucher.prompt.create.label.value"),
                    required: true,
                    min: 0
                },
                {
                    type: "select",
                    label: t("voucher.prompt.create.label.currency"),
                    required: true,
                    options: currencies.map(currency => ({
                        id: currency,
                        name: currency
                    }))
                },
                {
                    type: "date",
                    label: t("voucher.prompt.create.label.expiration"),
                    required: false
                }
            ] as const,
            (code: string, issuer: string, value: number, currency: string, expiration: string) => createVoucher(code, issuer, value, currency, expiration && new Date(expiration)),
            t("voucher.prompt.create.confirmed"),
            t("voucher.prompt.create.failed")
        )

    const showSynchronizePhotosToast = (synchronizePhotos: (path: string, expiration: Date) => Promise<void>) =>
        showFormToast(
            t("photo.prompt.synchronize.message"),
            [
                {
                    type: "text",
                    label: t("photo.prompt.synchronize.label.path"),
                    required: true
                },
                {
                    type: "datetime-local",
                    label: t("photo.prompt.synchronize.label.expiration"),
                    required: true
                }
            ],
            (path: string, expiration: string) => synchronizePhotos(path, new Date(expiration)),
            t("photo.prompt.synchronize.confirmed"),
            t("photo.prompt.synchronize.failed")
        )

    const showCreateGeographicalRegionToast = (countryCategories: Category[], createGeographicalRegion: (name: string, category: CategoryCategory, geoJson: object, country?: string, radius?: number) => Promise<GeographicalRegion>, templateRegion?: GeographicalRegion) =>
        showFormToast(
            t("region.prompt.create.geographical.message"),
            [
                {
                    type: "text",
                    label: t("region.prompt.create.geographical.label.name"),
                    required: true,
                    defaultValue: templateRegion?.category?.name
                },
                {
                    type: "select",
                    label: t("region.prompt.create.geographical.label.category"),
                    required: true,
                    options: Object.values(CategoryCategory).map(categoryCategory => ({
                        id: categoryCategory,
                        name: t(`category.category.${categoryCategory}`)
                    })),
                    defaultValue: templateRegion?.category?.category
                },
                {
                    type: "text",
                    label: t("region.prompt.create.geographical.label.geojson"),
                    required: true,
                    defaultValue: templateRegion?.geoJson && JSON.stringify(templateRegion?.geoJson)
                },
                {
                    type: "select",
                    label: t("region.prompt.create.geographical.label.country"),
                    required: false,
                    options: countryCategories.map(countryCategory => ({
                        id: countryCategory.name,
                        name: countryCategory.name
                    })),
                    defaultValue: templateRegion?.countryCategory?.name
                },
                {
                    type: "number",
                    label: t("region.prompt.create.geographical.label.radius"),
                    required: false,
                    min: 0,
                    defaultValue: templateRegion?.radius
                }
            ] as const,
            (name: string, category: CategoryCategory, geoJson: string, country?: string, radius?: number) => createGeographicalRegion(name, category, JSON.parse(geoJson), country, radius),
            t("region.prompt.create.geographical.confirmed"),
            t("region.prompt.create.geographical.failed")
        )

    const showCreateCompositeRegionToast = (createCompositeRegion: (name: string, category: CategoryCategory, includedCategoryNames: string[], excludedCategoryNames: string[]) => Promise<CompositeRegion>) =>
        showFormToast(
            t("region.prompt.create.composite.message"),
            [
                {
                    type: "text",
                    label: t("region.prompt.create.geographical.label.name"),
                    required: true
                },
                {
                    type: "select",
                    label: t("region.prompt.create.geographical.label.category"),
                    required: true,
                    options: Object.values(CategoryCategory).map(categoryCategory => ({
                        id: categoryCategory,
                        name: t(`category.category.${categoryCategory}`)
                    }))
                },
                {
                    type: "text",
                    label: t("region.prompt.create.composite.label.included"),
                    required: true
                },
                {
                    type: "text",
                    label: t("region.prompt.create.composite.label.included"),
                    required: false
                }
            ],
            (name, category, includedCategoryNames, excludedCategoryNames) => createCompositeRegion(name, category as CategoryCategory, includedCategoryNames.split(",").map(name => name.trim()).filter(Boolean), excludedCategoryNames?.split(",")?.map(name => name.trim())?.filter(Boolean)),
            t("region.prompt.create.composite.confirmed"),
            t("region.prompt.create.composite.failed")
        )

    const showCreateSelectedRegionToast = (countryCategories: Category[], createGeoJsonRegion: (geoJson: object) => object, extractGeoJsonFeatures: (geoJson: object) => any[], createGeographicalRegion: (name: string, category: CategoryCategory, geoJson: object, country?: string, radius?: number) => Promise<GeographicalRegion>, createCompositeRegion: (name: string, category: CategoryCategory, includedCategoryNames: string[], excludedCategoryNames: string[]) => Promise<CompositeRegion>) =>
        showBranchingToast(
            t("region.prompt.create.selected.message"),
            {
                geographical: {
                    name: t("region.prompt.create.selected.label.geographical"),
                    handle: () => showCreateGeographicalRegionToast(countryCategories, createGeographicalRegion)
                },
                composite: {
                    name: t("region.prompt.create.selected.label.composite"),
                    handle: () => showCreateCompositeRegionToast(createCompositeRegion)
                },
                multiple: {
                    name: t("region.prompt.create.selected.label.multiple"),
                    handle: () => showCreateMultipleGeographicalRegionsToast(async geoJson => {
                        const geoFeatures = extractGeoJsonFeatures(geoJson)
                        for (const geoFeature of geoFeatures) {
                            try {
                                const templateRegion = {
                                    category: {
                                        id: "",
                                        // TODO: Use the value from the previous toast (if any).
                                        category: CategoryCategory.Administrative,
                                        name: Object.keys(geoFeature.properties).map(property => property + " - " + geoFeature.properties[property]).join(", ")
                                    },
                                    // TODO: Use the value from the previous toast (if any).
                                    radius: 0,
                                    geoJson: createGeoJsonRegion(geoFeature.geometry)
                                }
                                await showCreateGeographicalRegionToast(countryCategories, createGeographicalRegion, templateRegion)
                            }
                            catch (error) {
                                continue
                            }
                        }
                    })
                }
            }
        )

    return {
        showCreateSelectedRegionToast,
        showCreateCompositeRegionToast,
        showCreateGeographicalRegionToast,
        showSynchronizePhotosToast,
        showCreateVoucherToast,
        showCreateDocumentToast,
        showCreateSubscriptionToast,
        showCreateFlightToast,
        showCreateNegativeTimeTrackingEventToast,
        showCreatePlannedWorkToast,
        showCreateOvertimeToast,
        showLoadTripToast,
        showMoveTripToast,
        showCreatePlaceToast,
        showSubtractVoucherValueToast,
        showOverwriteCompositeRegionToast,
        showOverwriteGeographicalRegionToast,
        showUpdateHighlightAttributesToast,
        showUploadPhotosToast,
        showSelectHighlightsToast,
        showAssignCategoryToast,
        showAssignAirlineCodeToast,
        showUpdateCategoryToast,
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