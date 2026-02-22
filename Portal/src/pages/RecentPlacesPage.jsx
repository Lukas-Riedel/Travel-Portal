import { useCategories } from "../hooks/useCategories.js"
import { useEffect, useMemo, useRef, useState } from "react"
import PlaceMap from "../components/PlaceMap.jsx"
import PlaceSummaryList from "../components/PlaceSummaryList.jsx"
import { TailSpin } from "react-loader-spinner"
import { useAuth } from "../contexts/AuthContext.jsx"
import { useUpcomingOrCurrentTrip } from "../hooks/useUpcomingOrCurrentTrip.js"
import TripSummary from "../components/TripSummary.jsx"
import { UserRole } from "../types/CoreSwaggerTypes.ts"
import { useRegularPlaces } from "../hooks/useRegularPlaces.ts"
import { getCurrentOrMaximumAllowedTimestamp } from "../utils/timeUtils.ts"

const limitStep = 10
const maxDistance = 500

export default function RecentPlacesPage() {
    const { hasRole } = useAuth()

    const [displayedPlaces, setDisplayedPlaces] = useState(undefined)
    const [currentLimit, setCurrentLimit] = useState(limitStep)
    const isFetching = useRef(false)

    const { places } = useRegularPlaces({ include: ["categories", "dates", "excerpt"], limit: currentLimit, maxEnd: getCurrentOrMaximumAllowedTimestamp(), sort: "-oldest" })
    const countryCategories = useCategories({ categories: ["country"] })

    const { trip: upcomingOrCurrentTrip, createTripNote, removeTripNote } = useUpcomingOrCurrentTrip()

    useEffect(() => {
        if (places?.length) {
            if (places.length === limitStep) {
                const breakIndex = places.findIndex((place, i) => i === 0 ? false : place.getHaversineDistanceTo(places[i - 1]) > maxDistance)

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
            setCurrentLimit(prev => prev + limitStep)
            setTimeout(() => {
                isFetching.current = false
            }, 300)
        }

        window.addEventListener("scroll", onScroll)
        return () => window.removeEventListener("scroll", onScroll)
    }, [])

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])

    return hasRole(UserRole.PlaceRead) && (
        <>
            <div className="h-[400px] md:h-[700px] my-4">
                <PlaceMap
                    places={displayedPlaces}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)}
                />
            </div>
            {(hasRole(UserRole.PortalFutureRead) || upcomingOrCurrentTrip?.isCurrent()) && (
                <TripSummary
                    trip={upcomingOrCurrentTrip}
                    onNoteAdded={hasRole(UserRole.TripNoteEdit) && createTripNote}
                    onNoteRemoved={hasRole(UserRole.TripNoteEdit) && removeTripNote} />
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
