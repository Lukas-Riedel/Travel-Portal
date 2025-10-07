import { useCategories } from "../hooks/useCategories.js"
import { useEffect, useMemo, useRef, useState } from "react"
import PlaceMap from "../components/PlaceMap.jsx"
import StatisticsPanel from "../components/StatisticsPanel.jsx"
import { useStatistics } from "../hooks/useStatistics.js"
import PlaceSummaryList from "../components/PlaceSummaryList.jsx"
import { useRegularPlaces } from "../hooks/useRegularPlaces.js"
import { TailSpin } from "react-loader-spinner"
import { useAuth } from "../contexts/AuthContext.jsx"
import { useUpcomingOrCurrentTrip } from "../hooks/useUpcomingOrCurrentTrip.js"
import TripSummary from "../components/TripSummary.jsx"

const limitStep = 10

export default function RecentPlacesPage() {
    const { isAdmin } = useAuth()

    const [displayedPlaces, setDisplayedPlaces] = useState(undefined)
    const [currentLimit, setCurrentLimit] = useState(limitStep)
    const isFetching = useRef(false)

    // TODO: Add support for skipping and just append new places.
    const places = useRegularPlaces({ include: "categories,dates,excerpt", limit: currentLimit, maxEnd: Math.round(Date.now() / 1000), sort: "-oldest" })
    const countryCategories = useCategories({ categories: "country" })
    const statistics = useStatistics()

    const { trip: upcomingOrCurrentTrip } = useUpcomingOrCurrentTrip()

    useEffect(() => {
        if (places?.length) {
            setDisplayedPlaces(places)
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

    return (
        <>
            <div className="h-[400px] md:h-[700px] my-4">
                <PlaceMap
                    places={displayedPlaces}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)}
                />
            </div>
            <StatisticsPanel statistics={statistics} />
            {(isAdmin || upcomingOrCurrentTrip?.isCurrent()) && (
                <TripSummary trip={upcomingOrCurrentTrip} />
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
