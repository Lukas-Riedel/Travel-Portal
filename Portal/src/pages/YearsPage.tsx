import PlaceMap from "../components/PlaceMap.jsx"
import StaticMapFrame from "../components/StaticMapFrame.tsx"
import TripTable from "../components/TripTable.jsx"
import YearTripTileGrid from "../components/YearTripTileGrid.jsx"
import { useAuth } from "../contexts/AuthContext.jsx"
import { useCountryCategoriesMap } from "../hooks/useCountryCategoriesMap.ts"
import { useRegularTrips } from "../hooks/useRegularTrips.js"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces.js"
import { useYears } from "../hooks/useYears.js"
import { PlaceSortingStrategy, UserRole } from "../types/CoreSwaggerTypes.ts"
import { getCurrentYear } from "../utils/timeUtils.ts"

export default function YearsPage() {
    const { hasRole } = useAuth()

    const years = useYears()
    const { places } = useTimeFilteredRegularPlaces({ sort: PlaceSortingStrategy.ValueScore })
    const { trips } = useRegularTrips()
    const countryCategoriesMap = useCountryCategoriesMap()

    return hasRole(UserRole.YearRead) && (
        <>
            <StaticMapFrame>
                <PlaceMap
                    places={places ?? null}
                    placeMainCategorySelector={place => countryCategoriesMap?.get(place.country) ?? null} />
            </StaticMapFrame>
            {hasRole(UserRole.PortalFutureRead) && (
                <TripTable trips={trips?.filter(trip => trip?.isFuture()) ?? null} />
            )}
            {(years?.filter(year => year.mainHighlight)?.map(year => year.id) ?? [getCurrentYear()]).map(year => (
                <YearTripTileGrid
                    key={year}
                    year={year}
                    trips={trips ?? null} />
            ))}
        </>
    )
}
