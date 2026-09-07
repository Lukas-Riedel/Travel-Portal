import { useEffect, useMemo, useRef, useState } from "react"
import { useAuth } from "../contexts/AuthContext.jsx"
import { useEvents } from "../hooks/useEvents.ts"
import { useRegularPlaces } from "../hooks/useRegularPlaces.ts"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces.ts"
import { useCategories } from "../hooks/useCategories.js"
import { useUpcomingOrCurrentTrip } from "../hooks/useUpcomingOrCurrentTrip.js"
import MapFrame from "../components/MapFrame.tsx"
import PlaceMap from "../components/PlaceMap.jsx"
import PlaceSummaryList from "../components/PlaceSummaryList.jsx"
import TripSummary from "../components/TripSummary.tsx"
import { TailSpin } from "react-loader-spinner"
import { CategoryCategory, PlaceIncludedEntity, PlaceSortingStrategy, UserRole } from "../types/CoreSwaggerTypes.ts"
import { getCurrentOrMaximumAllowedTimestamp, getMaximumAllowedTimetamp } from "../utils/timeUtils.ts"
import { useCountryCategoriesMap } from "../hooks/useCountryCategoriesMap.ts"
import { Place } from "../classes/Place.ts"

const LIMIT_STEP = 10
const MAX_DISTANCE = 2000

export default function RecentPlacesPage() {
    const { hasRole } = useAuth()
    const { publishPhotosUploadingTriggeredEvent } = useEvents()

    const [displayedPlaces, setDisplayedPlaces] = useState<Place[] | null>(null)
    const [currentLimit, setCurrentLimit] = useState(LIMIT_STEP)
    const isFetching = useRef(false)

    const { places } = useRegularPlaces({ include: [PlaceIncludedEntity.Categories, PlaceIncludedEntity.Dates, PlaceIncludedEntity.Excerpt], limit: currentLimit, maxEnd: getCurrentOrMaximumAllowedTimestamp(), sort: PlaceSortingStrategy.ValueOldestWithTrip })
    const { places: allPlaces } = useTimeFilteredRegularPlaces({ sort: PlaceSortingStrategy.ValueScore })
    const countryCategoriesMap = useCountryCategoriesMap()
    const { trip: upcomingOrCurrentTrip, createTripNote, removeTripNote } = useUpcomingOrCurrentTrip()

    useEffect(() => {
        if (places?.length) {
            if (places.length === LIMIT_STEP) {
                const breakIndex = places.findIndex((place, i) => i === 0 ? false : place.getHaversineDistanceTo(places[i - 1]) > MAX_DISTANCE)

                const filteredPlaces = breakIndex === -1 ? places : places.slice(0, breakIndex)
                setDisplayedPlaces(filteredPlaces)
            }
            else {
                setDisplayedPlaces(places)
            }
        }
    }, [places?.length])

    useEffect(() => {
        const onScroll = () => {
            if (isFetching.current) {
                return
            }

            if (window.innerHeight + window.scrollY < document.body.offsetHeight - 100) {
                return
            }

            isFetching.current = true
            setCurrentLimit(previous => previous + LIMIT_STEP)
            setTimeout(() => {
                isFetching.current = false
            }, 300)
        }

        window.addEventListener("scroll", onScroll)
        return () => window.removeEventListener("scroll", onScroll)
    }, [])

    return hasRole(UserRole.PlaceRead) && (
        <>
            <MapFrame>
                <PlaceMap
                    places={allPlaces}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)}
                />
            </MapFrame>
            {(hasRole(UserRole.PortalFutureRead) || upcomingOrCurrentTrip?.isCurrent()) && upcomingOrCurrentTrip?.end < getMaximumAllowedTimetamp() && (
                <TripSummary
                    trip={upcomingOrCurrentTrip}
                    displayDeviceData={hasRole(UserRole.PortalFutureRead)}
                    onNoteAdded={hasRole(UserRole.TripNoteEdit) && createTripNote}
                    onNoteRemoved={hasRole(UserRole.TripNoteEdit) && removeTripNote}
                    onPhotosAdded={hasRole(UserRole.PlaceAlbumEdit) && publishPhotosUploadingTriggeredEvent} />
            )}
            <PlaceSummaryList places={displayedPlaces} />
            {displayedPlaces && isFetching.current && (
                <div className="flex justify-center items-center min-h-[400px]">
                    <TailSpin
                        color="black"
                        height={80}
                        width={80} />
                </div>
            )}
        </>
    )
}
