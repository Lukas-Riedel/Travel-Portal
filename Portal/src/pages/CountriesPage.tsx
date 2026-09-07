import { useCategories } from "../hooks/useCategories"
import MapFrame from "../components/MapFrame.tsx"
import CategoryTileGrid from "../components/CategoryTileGrid"
import { useMemo } from "react"
import PlaceMap from "../components/PlaceMap"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces"
import { useAuth } from "../contexts/AuthContext.tsx"
import { CategoryCategory, PlaceSortingStrategy, UserRole } from "../types/CoreSwaggerTypes.ts"
import { useCountryCategoriesMap } from "../hooks/useCountryCategoriesMap.ts"

export default function CountriesPage() {
    const { hasRole } = useAuth()

    const { places } = useTimeFilteredRegularPlaces({ sort: PlaceSortingStrategy.ValueScore })
    const countryCategoriesMap = useCountryCategoriesMap()

    const countries = useMemo(() => {
        if (!places || !countryCategoriesMap) {
            return null
        }

        if (places.length === 0 || countryCategoriesMap.size === 0) {
            return []
        }

        return Object.entries(places.reduce<Record<string, number>>((acc, place) => ({ ...acc, [place.country]: (acc[place.country] ?? 0) + place.score }), {}))
            .sort(([, scoreA], [, scoreB]) => scoreB - scoreA)
            .map(([country]) => countryCategoriesMap.get(country))
            .filter(Boolean)
    }, [places, countryCategoriesMap, countryCategoriesMap])

    return hasRole(UserRole.CategoryRead) && (
        <>
            <MapFrame>
                <PlaceMap
                    places={places}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)} />
            </MapFrame>
            <CategoryTileGrid categories={countries} />
        </>
    )
}
