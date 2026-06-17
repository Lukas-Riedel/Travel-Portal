import { useMemo } from "react"
import PlaceMap from "../components/PlaceMap.jsx"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces.js"
import { useCategories } from "../hooks/useCategories.js"
import { useRegularTrips } from "../hooks/useRegularTrips.js"
import TripTable from "../components/TripTable.jsx"
import { useAuth } from "../contexts/AuthContext.jsx"
import { useYears } from "../hooks/useYears.js"
import YearTripTileGrid from "../components/YearTripTileGrid.jsx"
import { UserRole } from "../types/CoreSwaggerTypes.ts"

export default function YearsPage() {
    const { hasRole } = useAuth()

    const years = useYears()
    const { places } = useTimeFilteredRegularPlaces({ sort: "-score" })
    const { trips } = useRegularTrips()
    const countryCategories = useCategories({ categories: ["country"] })

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])

    return hasRole(UserRole.YearRead) && (
        <>
            <div className="h-[400px] md:h-[700px] my-4">
                <PlaceMap
                    places={places}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)} />
            </div>
            {hasRole(UserRole.PortalFutureRead) && (
                <TripTable trips={trips?.filter(trip => trip?.isFuture())} />
            )}
            {(years?.filter(year => year.mainHighlight)?.map(year => year.id) ?? [new Date().getFullYear()]).map(year => (
                <YearTripTileGrid
                    key={year}
                    year={year}
                    trips={trips} />
            ))}
        </>
    )
}
