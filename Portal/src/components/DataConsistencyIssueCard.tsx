import { Wrench } from "lucide-react"
import LoadingCard from "./LoadingCard.tsx"
import { fromUnixTime } from "date-fns"
import { listCategories } from "../clients/coreClient.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { useFormatters } from "../hooks/useFormatters.ts"
import { CategoryCategory, type Airline, type Airport, type Album, type Category, type CategoryIdentifier, type CategoryMetadata, type DataConsistencyIssue, type Fitness, type Flight, type GeographicalRegion, type Photo, type TimeBasedFitnessCollection } from "../types/CoreSwaggerTypes.ts"
import type { Place } from "../classes/Place.ts"
import { DataConsistencyIssueName } from "../types/DataConsistencyIssueName.ts"
import { useTranslation } from "react-i18next"
import { useAppNavigate } from "../hooks/useAppNavigate.ts"
import { AdminNavigationTarget } from "../classes/AdminNavigationTarget.ts"
import { AdminMenuTabName } from "../types/AdminMenuTabName.ts"
import type { Trip } from "../classes/Trip.ts"
import { formatTimestamp } from "../utils/timeUtils.ts"
import Card from "./Card.tsx"
import { useMemo } from "react"

interface DataConsistencyIssueCardProps {
    dataConsistencyIssue: DataConsistencyIssue | null
    airlines: Airline[] | null
    onAirlineCodeAssigned?: (airlineId: string, code: string) => Promise<Airline>
    onFitnessReplaced?: (timestamp: number, steps: number, seconds: number, distance: number, overwrite: boolean) => Promise<Fitness>
    onAirportNameChanged?: (airportId: string, longName: string) => Promise<Airport>
    onAirlineLogoChanged?: (airportId: string, country: string) => Promise<Airline>
    onAllAlbumsInvalidated?: () => Promise<void>
    onPhotoInvalidated?: (photoId: string) => Promise<void>
    onGeographicalExtensionCategoryAdded?: (name: string, country: string, category: string, latitude: number, longitude: number) => Promise<GeographicalRegion>
    onPlaceRemoved?: (placeId: string) => Promise<void>
    onFlightLogged?: (flight: string, from: string, to: string, scheduledDeparture: number) => Promise<Flight>
    onCategoryMetadataChanged?: (categoryId: string, metadata: CategoryMetadata) => Promise<Category>
    onAirportCountryChanged?: (airportId: string, country: string) => Promise<Airport>
    onPlaceCountryChanged?: (placeId: string, country: string) => Promise<Place>
}

interface DataConsistencyIssueHandler<T> {
    name: string
    isResolvable: boolean
    getProperties: (context: T) => Record<string, string | number>
    resolve: (context: T) => void
}

export default function DataConsistencyIssueCard({ dataConsistencyIssue, airlines, onAirlineCodeAssigned, onFitnessReplaced, onAirportNameChanged, onAirlineLogoChanged,
    onAllAlbumsInvalidated, onPhotoInvalidated, onGeographicalExtensionCategoryAdded, onPlaceRemoved, onFlightLogged, onCategoryMetadataChanged, onAirportCountryChanged,
    onPlaceCountryChanged }: DataConsistencyIssueCardProps) {
    const { t } = useTranslation()
    const navigate = useAppNavigate()
    const { formatDuration, formatEvents, formatKilometers, formatSteps } = useFormatters()
    const { showRemoveAlbumToast, showLogFlightToast, showRemovePhotoToast, showUpdatePlaceCountryToast, showUpdateAirportNameToast, showAssignCategoryToast, showRemovePlaceToast,
        showUpdateAirportCountryToast, showUpdateAirlineLogoToast, showReplaceFitnessToast, showUpdateCategoryToast, showAssignAirlineCodeToast } = usePredefinedUserInput()

    const openGoogleCalendar = (timestamp: number) => window.open(((d: Date) => `https://calendar.google.com/calendar/u/0/r/day/${d.getFullYear()}/${d.getMonth() + 1}/${d.getDate()}`)(fromUnixTime(timestamp)), "_blank")

    const handleFitnessReplaced = ({ timestamp, fitness }: TimeBasedFitnessCollection) => {
        if (onFitnessReplaced) {
            showReplaceFitnessToast(fitness, fitnessIndex => onFitnessReplaced(timestamp, fitness[fitnessIndex].steps,
                fitness[fitnessIndex].seconds, fitness[fitnessIndex].distance, true))
        }
    }

    const handleCategoryMetadataChanged = (category: Category) => {
        if (onCategoryMetadataChanged) {
            showUpdateCategoryToast(category, metadata => onCategoryMetadataChanged(category.id, metadata))
        }
    }

    const handleAlbumRemoved = (album: Album) => {
        window.open(album.permalink, "_blank")

        if (onAllAlbumsInvalidated) {
            showRemoveAlbumToast(onAllAlbumsInvalidated)
        }
    }

    const handlePhotoRemoved = (photo: Photo) => {
        window.open(photo.permalink, "_blank")

        if (onPhotoInvalidated) {
            showRemovePhotoToast(() => onPhotoInvalidated(photo.id))
        }
    }

    const handleAirlineCodeAssigned = (code: string) => {
        if (onAirlineCodeAssigned) {
            showAssignAirlineCodeToast(airlines, airlineId => onAirlineCodeAssigned(airlineId, code))
        }
    }

    const handleAdministrativeCategoryAssigned = async (place: Place) => {
        if (onGeographicalExtensionCategoryAdded) {
            const categoryCandidates = await listCategories({ country: place.country, categories: [CategoryCategory.Administrative] })
            return showAssignCategoryToast(categoryCandidates, categoryName => onGeographicalExtensionCategoryAdded(categoryName, place.country, CategoryCategory.Administrative, place.latitude, place.longitude))
        }
    }

    const handlePlaceCountryAssigned = (place: Place) => {
        if (onPlaceCountryChanged) {
            showUpdatePlaceCountryToast(country => onPlaceCountryChanged(place.id, country))
        }
    }

    const handlePlaceRemoved = (places: Place[]) => {
        if (onPlaceRemoved) {
            showRemovePlaceToast(places.filter(place => !place.dates?.length), onPlaceRemoved)
        }
    }

    const handleAirportNameChanged = (airport: Airport) => {
        if (onAirportNameChanged) {
            showUpdateAirportNameToast(name => onAirportNameChanged(airport.id, name))
        }
    }

    const handleAirportCountryAssigned = (airport: Airport) => {
        if (onAirportCountryChanged) {
            showUpdateAirportCountryToast(country => onAirportCountryChanged(airport.id, country))
        }
    }

    const handleAirlineLogoAssigned = (airline: Airline) => {
        if (onAirlineLogoChanged) {
            showUpdateAirlineLogoToast(logo => onAirlineLogoChanged(airline.id, logo))
        }
    }

    const handleFlightLogged = (flight: Flight) => {
        if (onFlightLogged) {
            showLogFlightToast(() => onFlightLogged(flight.flight, flight.from.shortName, flight.to.shortName, flight.start))
        }
    }

    // TODO: Is it worth it to wrap this by useMemo?
    const dataConsistencyIssueHandlers: Record<DataConsistencyIssueName, DataConsistencyIssueHandler<any>> = {
        [DataConsistencyIssueName.ConflictingFitnessRecords]: {
            name: t("issue.fitness.conflict.name"),
            isResolvable: !!onFitnessReplaced,
            getProperties: ({ timestamp, fitness }: TimeBasedFitnessCollection) => {
                const records = fitness.map((fitnessRecord, i) => [
                    t("issue.fitness.conflict.label.record", { index: i + 1 }),
                    `<ul class="list-disc ml-6">
                        <li>${formatSteps(fitnessRecord.steps)}</li>
                        <li>${formatKilometers(Number((Math.round(fitnessRecord.distance) / 1000).toFixed(1)))}</li>
                        <li>${formatDuration(fitnessRecord.seconds)}</li>
                    </ul>`
                ])

                return {
                    [t("issue.fitness.conflict.label.timestamp")]: formatTimestamp(timestamp, t("general.format.datetime.year.included")),
                    ...Object.fromEntries(records)
                }
            },
            resolve: handleFitnessReplaced
        },
        [DataConsistencyIssueName.CountryWithIncompleteMetadata]: {
            name: t("issue.category.country.incomplete.name"),
            isResolvable: !!onCategoryMetadataChanged,
            getProperties: (category: Category) => (
                {
                    [t("issue.category.country.incomplete.label.name")]: category.name,
                    [t("issue.category.country.incomplete.label.color")]: category.metadata?.color || "N/A",
                    [t("issue.category.country.incomplete.label.unicode")]: category.metadata?.unicode || "N/A",
                    [t("issue.category.country.incomplete.label.calendar")]: category.metadata?.publicHolidaysCalendar || "N/A"
                }
            ),
            resolve: handleCategoryMetadataChanged
        },
        [DataConsistencyIssueName.AlbumWithoutPlace]: {
            name: t("issue.album.standalone.name"),
            isResolvable: true,
            getProperties: (album: Album) => (
                {
                    [t("issue.album.standalone.label.name")]: album.name,
                    [t("issue.album.standalone.label.photos")]: album.imagesCount
                }
            ),
            resolve: handleAlbumRemoved
        },
        [DataConsistencyIssueName.EmptyAlbum]: {
            name: t("issue.album.empty.name"),
            isResolvable: true,
            getProperties: (album: Album) => (
                {
                    [t("issue.album.empty.label.name")]: album.name
                }
            ),
            resolve: handleAlbumRemoved
        },
        [DataConsistencyIssueName.ReplacedPhoto]: {
            name: t("issue.photo.replaced.name"),
            isResolvable: true,
            getProperties: (photo: Photo) => (
                {
                    [t("issue.photo.replaced.label.timestamp")]: formatTimestamp(photo.timestamp, t("general.format.datetime.year.included"))
                }
            ),
            resolve: handlePhotoRemoved
        },
        [DataConsistencyIssueName.AirlineCodeWithoutAirline]: {
            name: t("issue.airline.code.unassigned.name"),
            isResolvable: !!onAirlineCodeAssigned,
            getProperties: (code: string) => (
                {
                    [t("issue.airline.code.unassigned.label.code")]: code
                }
            ),
            resolve: handleAirlineCodeAssigned
        },
        [DataConsistencyIssueName.PlaceWithoutAdministrativeCategory]: {
            name: t("issue.place.category.unassigned.administrative.name"),
            isResolvable: !!onGeographicalExtensionCategoryAdded,
            getProperties: (place: Place) => (
                {
                    [t("issue.place.category.unassigned.administrative.label.name")]: place.name,
                    [t("issue.place.category.unassigned.administrative.label.country")]: place.country
                }
            ),
            resolve: handleAdministrativeCategoryAssigned
        },
        [DataConsistencyIssueName.PlaceWithoutCountry]: {
            name: t("issue.place.category.unassigned.country.name"),
            isResolvable: !!onPlaceCountryChanged,
            getProperties: (place: Place) => (
                {
                    [t("issue.place.category.unassigned.country.label.name")]: place.name,
                }
            ),
            resolve: handlePlaceCountryAssigned
        },
        [DataConsistencyIssueName.NonReviewedPlace]: {
            name: t("issue.place.nonreviewed.name"),
            isResolvable: true,
            getProperties: (place: Place) => (
                {
                    [t("issue.place.nonreviewed.label.name")]: place.name,
                    [t("issue.place.nonreviewed.label.country")]: place.country
                }
            ),
            resolve: navigate
        },
        [DataConsistencyIssueName.CountryWithoutAdministrativeDivision]: {
            name: t("issue.category.country.nondivided.name"),
            isResolvable: true,
            getProperties: (category: Category) => (
                {
                    [t("issue.category.country.nondivided.label.name")]: category.name
                }
            ),
            resolve: (category: Category) => navigate(new AdminNavigationTarget(AdminMenuTabName.Regions, category.name))
        },
        [DataConsistencyIssueName.DateWithoutTime]: {
            name: t("issue.place.date.time.nonset.name"),
            isResolvable: true,
            getProperties: (place: Place) => (
                {
                    [t("issue.place.date.time.nonset.label.name")]: place.name,
                    [t("issue.place.date.time.nonset.label.country")]: place.country,
                    [t("issue.place.date.time.nonset.label.date")]: formatTimestamp(place.dates[0].start, t("general.format.date.year.included"))
                }
            ),
            resolve: (place: Place) => openGoogleCalendar(place.dates[0].end)
        },
        [DataConsistencyIssueName.TripWithoutTime]: {
            name: t("issue.trip.time.nonset.name"),
            isResolvable: true,
            getProperties: (trip: Trip) => (
                {
                    [t("issue.trip.time.nonset.label.name")]: trip.name,
                    [t("issue.trip.time.nonset.label.from")]: formatTimestamp(trip.start, t("general.format.date.year.included")),
                    [t("issue.trip.time.nonset.label.to")]: formatTimestamp(trip.end, t("general.format.date.year.included"))
                }
            ),
            resolve: (trip: Trip) => openGoogleCalendar(trip.start)
        },
        [DataConsistencyIssueName.LoggedFlightWithoutFlightEvent]: {
            name: t("issue.flight.nonlinked.name"),
            isResolvable: true,
            getProperties: (flight: Flight) => (
                {
                    [t("issue.flight.nonlinked.label.flight")]: flight.flight,
                    [t("issue.flight.nonlinked.label.from")]: flight.from.code,
                    [t("issue.flight.nonlinked.label.to")]: flight.to.code,
                    [t("issue.flight.nonlinked.label.departure")]: formatTimestamp(flight.start, t("general.format.datetime.year.included")),
                    [t("issue.flight.nonlinked.label.arrival")]: formatTimestamp(flight.end, t("general.format.datetime.year.included"))
                }
            ),
            resolve: (flight: Flight) => openGoogleCalendar(flight.start)
        },
        [DataConsistencyIssueName.DateWithIncorrectTime]: {
            name: t("issue.place.date.time.unaligned.name"),
            isResolvable: true,
            getProperties: (place: Place) => (
                {
                    [t("issue.place.date.time.unaligned.label.name")]: place.name,
                    [t("issue.place.date.time.unaligned.label.country")]: place.country,
                    [t("issue.place.date.time.unaligned.label.date")]: formatTimestamp(place.dates[0].start, t("general.format.date.year.included")),
                    [t("issue.place.date.time.unaligned.label.time")]: formatTimestamp(place.dates[0].start, t("general.format.time"))
                }
            ),
            resolve: (place: Place) => openGoogleCalendar(place.dates[0].start)
        },
        [DataConsistencyIssueName.DateWithIncorrectDuration]: {
            name: t("issue.place.date.duration.unaligned.name"),
            isResolvable: true,
            getProperties: place => (
                {
                    [t("issue.place.date.duration.unaligned.label.name")]: place.name,
                    [t("issue.place.date.duration.unaligned.label.country")]: place.country,
                    [t("issue.place.date.duration.unaligned.label.date")]: formatTimestamp(place.dates[0].start, t("general.format.date.year.included")),
                    [t("issue.place.date.duration.unaligned.label.duration")]: formatDuration(place.dates[0].end - place.dates[0].start)
                }
            ),
            resolve: (place: Place) => openGoogleCalendar(place.dates[0].start)
        },
        [DataConsistencyIssueName.DuplicatedPlace]: {
            name: t("issue.place.duplicated.name"),
            isResolvable: !!onPlaceRemoved,
            getProperties: (places: Place[]) => {
                const records = places.map((place, i) => [
                    t("issue.place.duplicated.label.place", { index: i + 1 }),
                    [place.name, place.dates?.length && formatEvents(place.dates.length)].filter(Boolean).join(", ")
                ])

                return {
                    [t("issue.place.duplicated.label.coordinates")]: `${places[0].latitude.toFixed(4)}, ${places[0].longitude.toFixed(4)}`,
                    ...Object.fromEntries(records)
                }
            },
            resolve: handlePlaceRemoved
        },
        [DataConsistencyIssueName.AirportWithoutLongName]: {
            name: t("issue.airport.unnamed.name"),
            isResolvable: !!onAirportNameChanged,
            getProperties: (airport: Airport) => (
                {
                    [t("issue.airport.unnamed.label.name")]: airport.shortName,
                    [t("issue.airport.unnamed.label.code")]: airport.code
                }
            ),
            resolve: handleAirportNameChanged
        },
        [DataConsistencyIssueName.AirportWithoutCountry]: {
            name: t("issue.airport.category.unassigned.country.name"),
            isResolvable: !!onAirportCountryChanged,
            getProperties: (airport: Airport) => (
                {
                    [t("issue.airport.unnamed.label.name")]: airport.shortName,
                    [t("issue.airport.unnamed.label.code")]: airport.code
                }
            ),
            resolve: handleAirportCountryAssigned
        },
        [DataConsistencyIssueName.AirlineWithoutLogo]: {
            name: t("issue.airline.logo.unassigned.name"),
            isResolvable: !!onAirlineLogoChanged,
            getProperties: (airline: Airline) => (
                {
                    [t("issue.airline.logo.unassigned.label.name")]: airline.name
                }
            ),
            resolve: handleAirlineLogoAssigned
        },
        [DataConsistencyIssueName.NonLoggedFlight]: {
            name: t("issue.flight.nonlogged.name"),
            isResolvable: !!onFlightLogged,
            getProperties: (flight: Flight) => (
                {
                    [t("issue.flight.nonlogged.label.flight")]: flight.flight,
                    [t("issue.flight.nonlogged.label.from")]: flight.from.shortName,
                    [t("issue.flight.nonlogged.label.to")]: flight.to.shortName,
                    [t("issue.flight.nonlogged.label.departure")]: formatTimestamp(flight.start, t("general.format.datetime.year.included")),
                    [t("issue.flight.nonlogged.label.arrival")]: formatTimestamp(flight.end, t("general.format.datetime.year.included"))
                }
            ),
            resolve: handleFlightLogged
        },
        [DataConsistencyIssueName.GeographicalRegionsWithSameName]: {
            name: t("issue.region.duplicated.name"),
            isResolvable: true,
            getProperties: (category: CategoryIdentifier) => (
                {
                    [t("issue.region.duplicated.label.name")]: category.name
                }
            ),
            resolve: (category: CategoryIdentifier) => navigate(new AdminNavigationTarget(AdminMenuTabName.Regions, category.name))
        }
    }

    const dataConsistencyIssueHandler = useMemo<DataConsistencyIssueHandler<any> | undefined>(() =>
        dataConsistencyIssue && dataConsistencyIssueHandlers[dataConsistencyIssue.name], [dataConsistencyIssue, dataConsistencyIssueHandlers])

    if (!dataConsistencyIssue) {
        return (
            <LoadingCard />
        )
    }

    return (
        <Card className="relative">
            <div className="text-lg font-semibold">
                {dataConsistencyIssueHandler?.name ?? dataConsistencyIssue.name}
            </div>
            <div className="my-2">
                {dataConsistencyIssueHandler ? (
                    <ul className="space-y-0.5">
                        {Object.entries(dataConsistencyIssueHandler.getProperties(dataConsistencyIssue.context)).map(([key, value]) => (
                            <li
                                key={key}
                                className="text-gray-700 truncate">
                                <span className="font-semibold">
                                    {key}:
                                </span>
                                {" "}
                                <span dangerouslySetInnerHTML={{ __html: value }} />
                            </li>
                        ))}
                    </ul>
                ) : (
                    <span className="break-words whitespace-normal">
                        {JSON.stringify(dataConsistencyIssue.context)}
                    </span>
                )}
            </div>
            <div className="text-gray-400 text-sm">
                {t("issue.scanned", { datetime: formatTimestamp(dataConsistencyIssue.timestamp, t("general.format.datetime.year.excluded")) })}
            </div>
            {dataConsistencyIssueHandler?.isResolvable && (
                <button
                    onClick={() => dataConsistencyIssueHandler.resolve(dataConsistencyIssue.context)}
                    className="absolute bottom-2 right-2 p-1 rounded text-orange-600 hover:bg-gray-100 transition-colors">
                    <Wrench size={16} />
                </button>
            )}
        </Card>
    )
}