import { Wrench } from "lucide-react"
import LoadingCard from "./LoadingCard.tsx"
import { getDateString, getDateTimeString, getTimeString } from "../utils/helpers"
import { formatDuration, formatEvents, formatKilometers, formatSteps } from "../utils/formatters"
import { fromUnixTime } from "date-fns"
import { useNavigate } from "react-router"
import { listCategories } from "../clients/coreClient"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"

export default function DataConsistencyIssueCard({ dataConsistencyIssue, airlines, onAirlineCodeAssigned, onFitnessReplaced, onAirportNameChanged, onAirlineLogoChanged,
    onAllAlbumsInvalidated, onPhotoInvalidated, onGeographicalExtensionCategoryAdded, onPlaceRemoved, onFlightLogged, onCategoryMetadataChanged, onAirportCountryChanged,
    onPlaceCountryChanged }) {
    const navigate = useNavigate()
    const { showRemoveAlbumToast, showLogFlightToast, showRemovePhotoToast, showUpdatePlaceCountryToast, showUpdateAirportNameToast, showAssignCategoryToast, showRemovePlaceToast,
        showUpdateAirportCountryToast, showUpdateAirlineLogoToast, showReplaceFitnessToast, showUpdateCategoryToast, showAssignAirlineCodeToast } = usePredefinedUserInput()

    const handleremoveAlbum = () => {
        showRemoveAlbumToast(onAllAlbumsInvalidated)
    }

    const dataConsistencyIssues = {
        "CONFLICTING_FITNESS_RECORDS": {
            name: "Konfliktní záznamy o aktivitě",
            getProperties: fitnessCollection => {
                const properties = {
                    "Čas": getDateTimeString(fitnessCollection.timestamp)
                }

                for (let i = 0; i < fitnessCollection.fitness.length; ++i) {
                    const fitness = fitnessCollection.fitness[i]
                    properties["Záznam " + (i + 1)] = `<ul class="list-disc ml-6"><li>${formatSteps(fitness.steps)}</li><li>${formatKilometers((Math.round(fitness.distance) / 1000).toFixed(1))}</li><li>${formatDuration(fitness.seconds)}</li></ul>`
                }

                return properties
            },
            resolve: fitnessCollection => showReplaceFitnessToast(fitnessCollection.fitness, fitnessIndex => onFitnessReplaced(fitnessCollection.timestamp, fitnessCollection.fitness[fitnessIndex].steps,
                    fitnessCollection.fitness[fitnessIndex].seconds, fitnessCollection.fitness[fitnessIndex].distance, true))
        },
        "COUNTRY_WITH_INCOMPLETE_METADATA": {
            name: "Stát s neúplnými metadaty",
            getProperties: category => (
                {
                    "Název": category.name,
                    "Barva": category.metadata?.color || "N/A",
                    "Unicode": category.metadata?.unicode || "N/A",
                    "Kalendář": category.metadata?.publicHolidaysCalendar || "N/A"
                }
            ),
            resolve: category => showUpdateCategoryToast(category, metadata => onCategoryMetadataChanged(category.id, metadata))
        },
        "ALBUM_WITHOUT_PLACE": {
            name: "Album bez přiřazeného místa",
            getProperties: album => (
                {
                    "Název": album.name,
                    "Počtet fotek": album.imagesCount
                }
            ),
            resolve: album => {
                window.open(album.permalink, "_blank")
                handleremoveAlbum()
            }
        },
        "EMPTY_ALBUM": {
            name: "Prázdné album",
            getProperties: album => (
                {
                    "Název": album.name
                }
            ),
            resolve: album => {
                window.open(album.permalink, "_blank")
                handleremoveAlbum()
            }
        },
        "REPLACED_PHOTO": {
            name: "Nahrazená fotka k odstranění",
            getProperties: photo => (
                {
                    "Čas pořízení": getDateTimeString(photo.timestamp)
                }
            ),
            resolve: photo => {
                window.open(photo.permalink, "_blank")
                showRemovePhotoToast(async () => onPhotoInvalidated(photo.id))
            }
        },
        "AIRLINE_CODE_WITHOUT_AIRLINE": {
            name: "Kód bez přiřazené aerolinky",
            getProperties: code => (
                {
                    "Kód": code
                }
            ),
            resolve: code => showAssignAirlineCodeToast(airlines, airlineId => onAirlineCodeAssigned(airlineId, code))
        },
        "COUNTRY_WITHOUT_ADMINISTRATIVE_DIVISION": {
            name: "Stát bez administrativního dělení",
            getProperties: category => (
                {
                    "Název": category.name
                }
            ),
            resolve: category => navigate(`/admin?tab=${encodeURIComponent("Správa regionů")}&key=${encodeURIComponent(category.name)}`)
        },
        "PLACE_WITHOUT_ADMINISTRATIVE_CATEGORY": {
            name: "Místo bez administrativní kategorie",
            getProperties: place => (
                {
                    "Název": place.name,
                    "Stát": place.country
                }
            ),
            resolve: async (place) => {
                const categories = await listCategories({ country: place.country, categories: ["administrative"] })
                return showAssignCategoryToast(categories, categoryName => onGeographicalExtensionCategoryAdded(categoryName, place.country, "administrative", place.latitude, place.longitude))
            }
        },
        "PLACE_WITHOUT_COUNTRY": {
            name: "Místo bez státní příslušnosti",
            getProperties: place => (
                {
                    "Název": place.name
                }
            ),
            resolve: place => showUpdatePlaceCountryToast(country => onPlaceCountryChanged(place.id, country))
        },
        "NON_REVIEWED_PLACE": {
            name: "Nezrevidované místo",
            getProperties: place => (
                {
                    "Název": place.name,
                    "Stát": place.country
                }
            ),
            resolve: place => navigate(`/place/${place.id}`)
        },
        "DATE_WITHOUT_TIME": {
            name: "Událost bez specifikovaného času",
            getProperties: place => (
                {
                    "Název": place.name,
                    "Stát": place.country,
                    "Výlet": place.dates[0].trip.name + " " + place.dates[0].trip.year,
                    "Datum": getDateString(place.dates[0].start)
                }
            ),
            resolve: place => window.open((d => `https://calendar.google.com/calendar/u/0/r/day/${d.getFullYear()}/${d.getMonth() + 1}/${d.getDate()}`)(fromUnixTime(place.dates[0].end)), "_blank")
        },
        "TRIP_WITHOUT_TIME": {
            name: "Výlet bez specifikovaného času",
            getProperties: trip => (
                {
                    "Název": trip.name + " " + trip.year,
                    "Od": getDateString(trip.start),
                    "Do": getDateString(trip.end)
                }
            ),
            resolve: trip => window.open((d => `https://calendar.google.com/calendar/u/0/r/week/${d.getFullYear()}/${d.getMonth() + 1}/${d.getDate()}`)(fromUnixTime(trip.start)), "_blank")
        },
        "LOGGED_FLIGHT_WITHOUT_FLIGHT_EVENT": {
            name: "Let bez události v kalendáři",
            getProperties: flight => (
                {
                    "Let": flight.flight,
                    "Z": flight.from.code,
                    "Do": flight.to.code,
                    "Odlet": getDateTimeString(flight.start),
                    "Přílet": getDateTimeString(flight.end)
                }
            ),
            resolve: flight => window.open((d => `https://calendar.google.com/calendar/u/0/r/day/${d.getFullYear()}/${d.getMonth() + 1}/${d.getDate()}`)(fromUnixTime(flight.end)), "_blank")
        },
        "DATE_WITH_INCORRECT_TIME": {
            name: "Událost s nesprávným časem začátku",
            getProperties: place => (
                {
                    "Název": place.name,
                    "Stát": place.country,
                    "Výlet": place.dates[0].trip.name + " " + place.dates[0].trip.year,
                    "Datum": getDateString(place.dates[0].start),
                    "Čas": getTimeString(place.dates[0].start)
                }
            ),
            resolve: place => window.open((d => `https://calendar.google.com/calendar/u/0/r/day/${d.getFullYear()}/${d.getMonth() + 1}/${d.getDate()}`)(fromUnixTime(place.dates[0].end)), "_blank")
        },
        "DATE_WITH_INCORRECT_DURATION": {
            name: "Událost s nesprávnou délkou trvání",
            getProperties: place => (
                {
                    "Název": place.name,
                    "Stát": place.country,
                    "Výlet": place.dates[0].trip.name + " " + place.dates[0].trip.year,
                    "Datum": getDateString(place.dates[0].start),
                    "Délka trvání": formatDuration(place.dates[0].end - place.dates[0].start)
                }
            ),
            resolve: place => window.open((d => `https://calendar.google.com/calendar/u/0/r/day/${d.getFullYear()}/${d.getMonth() + 1}/${d.getDate()}`)(fromUnixTime(place.dates[0].end)), "_blank")
        },
        "DUPLICATED_PLACE": {
            name: "Duplicitní místo",
            getProperties: places => (
                {
                    "Souřadnice": places[0].latitude.toFixed(4) + ", " + places[0].longitude.toFixed(4),
                    ...Object.fromEntries(places.map((place, idx) => ["Název " + (idx + 1), place.name + (place.dates?.length ? " (" + formatEvents(place.dates.length) + ")" : "")]))
                }
            ),
            resolve: places => showRemovePlaceToast(places.filter(place => !place.dates?.length), onPlaceRemoved)
        },
        "AIRPORT_WITHOUT_LONG_NAME": {
            name: "Nepojmenované letiště",
            getProperties: airport => (
                {
                    "Název": airport.shortName,
                    "Kód": airport.code
                }
            ),
            resolve: airport => showUpdateAirportNameToast(name => onAirportNameChanged(airport.id, name))
        },
        "AIRPORT_WITHOUT_COUNTRY": {
            name: "Letiště bez státní příslušnosti",
            getProperties: airport => (
                {
                    "Název": airport.shortName,
                    "Kód": airport.code
                }
            ),
            resolve: airport => showUpdateAirportCountryToast(country => onAirportCountryChanged(airport.id, country))
        },
        "AIRLINE_WITHOUT_LOGO": {
            name: "Aerolinka bez loga",
            getProperties: airline => (
                {
                    "Název": airline.name
                }
            ),
            resolve: airline => showUpdateAirlineLogoToast(logo => onAirlineLogoChanged(airline.id, logo))
        },
        "NON_LOGGED_FLIGHT": {
            name: "Nezalogovaný let",
            getProperties: flight => (
                {
                    "Let": flight.flight,
                    "Z": flight.from.shortName,
                    "Do": flight.to.shortName,
                    "Plánovaný odlet": getDateTimeString(flight.start),
                    "Plánovaný přílet": getDateTimeString(flight.end)
                }
            ),
            resolve: flight => showLogFlightToast(() => onFlightLogged(flight.flight, flight.from.shortName, flight.to.shortName, flight.start))
        },
        "GEOGRAPHICAL_REGIONS_WITH_SAME_NAME": {
            name: "Duplicitní název geogragického regionu",
            getProperties: category => (
                {
                    "Název": category.name
                }
            ),
            resolve: category => navigate(`/admin?tab=${encodeURIComponent("Správa regionů")}&key=${encodeURIComponent(category.name)}`)
        }
    }

    return dataConsistencyIssue ? (
        <div className="relative bg-white rounded-xl shadow-md max-w-xl mx-auto p-3 w-full">
            <div className="text-lg font-semibold">
                {dataConsistencyIssues[dataConsistencyIssue.name]?.name ?? dataConsistencyIssue.name}
            </div>
            <div className="my-2">
                {dataConsistencyIssue.context && dataConsistencyIssues[dataConsistencyIssue.name]?.getProperties ? (
                    <ul className="space-y-0.5">
                        {Object.entries(dataConsistencyIssues[dataConsistencyIssue.name]?.getProperties(dataConsistencyIssue.context)).map(([key, value]) => (
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
                Poslední sken: {getDateTimeString(dataConsistencyIssue.timestamp)}
            </div>
            {dataConsistencyIssues[dataConsistencyIssue.name]?.resolve && (
                <button
                    onClick={() => dataConsistencyIssues[dataConsistencyIssue.name]?.resolve(dataConsistencyIssue.context)}
                    className="absolute bottom-2 right-2 p-1 rounded text-orange-600 hover:bg-gray-100 transition-colors">
                    <Wrench size={16} />
                </button>
            )}
        </div>
    ) : (
        <LoadingCard />
    )
}