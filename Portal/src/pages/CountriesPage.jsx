import { useCategories } from "../hooks/useCategories.js"
import CategoryTileGrid from "../components/CategoryTileGrid.jsx"
import { useMemo } from "react"
import PlaceMap from "../components/PlaceMap.jsx"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces.js"
import { useAuth } from "../contexts/AuthContext.tsx"
import { UserRole } from "../types/CoreSwaggerTypes.ts"

export default function CountriesPage() {
    const { hasRole } = useAuth()

    const { places } = useTimeFilteredRegularPlaces({ sort: "-score" })
    const countryCategories = useCategories({ categories: ["country"] })

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])

    const countries = useMemo(() => {
        if (!places || !countryCategories) {
            return null
        }

        if (places.length === 0 || countryCategories.length === 0) {
            return []
        }

        const scoreByCountry = places.reduce((acc, place) => {
            acc[place.country] = (acc[place.country] || 0) + place.score
            return acc
        }, {})

        return Object.entries(scoreByCountry)
            .sort(([, scoreA], [, scoreB]) => scoreB - scoreA)
            .map(([country]) => countryCategoriesMap.get(country))
            .filter(Boolean)
    }, [places, countryCategoriesMap, countryCategories])

    return hasRole(UserRole.CategoryRead) && (
        <>
            <div className="h-[400px] md:h-[700px] my-4">
                <PlaceMap
                    places={places}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)} />
            </div>
            <CategoryTileGrid categories={countries} />
        </>
    )
}
