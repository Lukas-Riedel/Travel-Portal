
import CategoryTileGrid from "../components/CategoryTileGrid"
import PlaceMap from "../components/PlaceMap"
import StaticMapFrame from "../components/StaticMapFrame.tsx"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useCountryCategoriesMap } from "../hooks/useCountryCategoriesMap.ts"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces"
import { PlaceSortingStrategy, UserRole } from "../types/CoreSwaggerTypes.ts"

export default function CountriesPage() {
    const { hasRole } = useAuth()

    const { places } = useTimeFilteredRegularPlaces({ sort: PlaceSortingStrategy.ValueScore })
    const countryCategoriesMap = useCountryCategoriesMap()

    const countries = (() => {
        if (!places || !countryCategoriesMap) {
            return null
        }

        if (places.length === 0 || countryCategoriesMap.size === 0) {
            return []
        }

        return Object.entries(places.reduce<Record<string, number>>((acc, place) => ({ ...acc, [place.country]: (acc[place.country] ?? 0) + place.score }), {}))
            .sort(([, scoreA], [, scoreB]) => scoreB - scoreA)
            .map(([country]) => countryCategoriesMap.get(country))
            .filter((c): c is NonNullable<typeof c> => c != null)
    })()

    return hasRole(UserRole.CategoryRead) && (
        <>
            <StaticMapFrame>
                <PlaceMap
                    places={places ?? null}
                    placeMainCategorySelector={place => countryCategoriesMap?.get(place.country) ?? null} />
            </StaticMapFrame>
            <CategoryTileGrid categories={countries} />
        </>
    )
}
