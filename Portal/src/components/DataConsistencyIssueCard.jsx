import { Wrench } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import LoadingCard from "./LoadingCard"
import showFormToast from "./FormToast"
import showConfirmToast from "./ConfirmToast"
import { getDateString, getDateTimeString, getTimeString } from "../utils/helpers"
import { useApi } from "../hooks/useApi"
import { formatDuration, formatEvents, formatKilometers, formatSteps } from "../utils/formatters"
import { fromUnixTime } from "date-fns"
import { useNavigate } from "react-router"

export default function DataConsistencyIssueCard({ dataConsistencyIssue, airlines, onAirlineCodeAssigned, onFitnessOverwritten,
    onAllAlbumsInvalidated, onPhotoInvalidated, onGeographicalExtensionCategoryAdded, onPlaceRemoved, onFlightLogged, onRegionManagementOpened }) {
    const { isAdmin } = useAuth()
    const navigate = useNavigate()

    const { listCategories } = useApi()

    const handleRefreshAllAlbums = () => {
        showConfirmToast("Pokud bylo album odstraněno, je potřeba aktualizovat všechna alba. Přeješ si pokračovat?",
            "Všechna alba budou brzy aktualizována",
            "Nepodařilo se aktualizovat všchna alba",
            onAllAlbumsInvalidated
        )
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
            resolve: fitnessCollection => showFormToast(
                "Vyber preferovaný záznam:",
                [
                    { type: "select", required: true, options: Array.from({ length: fitnessCollection.fitness.length }, (_, index) => ({ id: index, name: "Záznam " + (index + 1) })) }
                ],
                "Záznam byl úspěšně nahrazen",
                "Nepodařilo se nahradit záznam",
                fitnessIndex => onFitnessOverwritten(fitnessCollection.timestamp, fitnessCollection.fitness[fitnessIndex].steps,
                    fitnessCollection.fitness[fitnessIndex].seconds, fitnessCollection.fitness[fitnessIndex].distance, true)
            )
        },
        "PLACE_HIGHLIGHTS_WITHOUT_QUALITY_ATTRIBUTES": {
            name: "Místo s highlighty bez atributů kvality",
            getProperties: place => (
                {
                    "Název": place.name,
                    "Stát": place.country
                }
            ),
            resolve: place => navigate(`/place/${place.id}`)
        },
        "TRIP_HIGHLIGHTS_WITHOUT_QUALITY_ATTRIBUTES": {
            name: "Výlet s highlighty bez atributů kvality",
            getProperties: trip => (
                {
                    "Název": trip.name + " " + trip.year,
                    "Od": getDateString(trip.start),
                    "Do": getDateString(trip.end)
                }
            ),
            resolve: trip => navigate(`/trip/${trip.id}`)
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
            resolve: category => navigate(`/category/${category.id}`)
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
                handleRefreshAllAlbums()
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
                handleRefreshAllAlbums()
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
                showConfirmToast("Pokud byla fotka odstraněna, je potřeba aktualizovat její alba. Přeješ si pokračovat?",
                    "Alba byla úspěšně aktualizována",
                    "Nepodařilo se aktualizovat alba",
                    async () => onPhotoInvalidated(photo.id)
                )
            }
        },
        "AIRLINE_CODE_WITHOUT_AIRLINE": {
            name: "Kód bez přiřazené aerolinky",
            getProperties: code => (
                {
                    "Kód": code
                }
            ),
            resolve: code => showFormToast(
                "Vyber aerolinku k přiřazení:",
                [
                    { type: "select", required: true, options: airlines?.map(airline => ({ id: airline.id, name: airline.name })) }
                ],
                "Aerolinka byla úspěšně přiřazena",
                "Nepodařilo se přiřadit aerolinku",
                async (airlineId) => onAirlineCodeAssigned(airlineId, code)
            )
        },
        "COUNTRY_WITHOUT_ADMINISTRATIVE_DIVISION": {
            name: "Stát bez administrativního dělení",
            getProperties: category => (
                {
                    "Název": category.name
                }
            ),
            resolve: onRegionManagementOpened
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
                const categories = await listCategories({ country: place.country, categories: "ADMINISTRATIVE" });
                return showFormToast(
                    "Vyber administrativní kategorii k přiřazení:",
                    [
                        { type: "select", required: true, options: categories.map(category => ({ id: category.name, name: category.name })) }
                    ],
                    "Kategorie byla úspěšně přiřazena",
                    "Při přiřazování kategorie došlo k chybě",
                    async (name) => onGeographicalExtensionCategoryAdded(name, place.country, "ADMINISTRATIVE", place.latitude, place.longitude)
                )
            }
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
        "LOGGED_FLIGHTS_WITHOUT_FLIGHT_EVENT": {
            name: "Let bez události v kalendáři",
            getProperties: flight => (
                {
                    "Let": flight.flight,
                    "Z": flight.from.name,
                    "Do": flight.to.name,
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
                    ...Object.fromEntries(places.map((place, idx) => ["Název " + (idx + 1), place.name + (place.dates.length > 0 ? " (" + formatEvents(place.dates.length) + ")" : "")]))
                }
            ),
            resolve: places => showFormToast(
                "Vyber místo k odstranění:",
                [
                    { type: "select", required: true, options: places.filter(place => place.dates.length === 0).map(place => ({ id: place.id, name: place.name })) }
                ],
                "Místo bylo úspěšně odstraněno",
                "Nepodařilo se odstranit místo",
                onPlaceRemoved
            )
        },
        "NON_LOGGED_FLIGHT": {
            name: "Nezalogovaný let",
            getProperties: flight => (
                {
                    "Let": flight.flight,
                    "Z": flight.from.name,
                    "Do": flight.to.name,
                    "Plánovaný odlet": getDateTimeString(flight.start),
                    "Plánovaný přílet": getDateTimeString(flight.end)
                }
            ),
            resolve: flight =>
                showConfirmToast("Opravdu chceš zalogovat vybraný let?",
                    "Let byl úspěšně zalogován",
                    "Nepodařilo se zalogovat let",
                    async () => onFlightLogged(flight.flight, flight.from.name, flight.to.name, flight.start)
                )
        },
        "GEOGRAPHICAL_REGIONS_WITH_SAME_NAME": {
            name: "Duplicitní název geogragického regionu",
            getProperties: category => (
                {
                    "Název": category.name
                }
            ),
            resolve: onRegionManagementOpened
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
                                <span className="font-semibold">{key}:</span> <span dangerouslySetInnerHTML={{ __html: value }} />
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
            {dataConsistencyIssues[dataConsistencyIssue.name]?.resolve && isAdmin && (
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