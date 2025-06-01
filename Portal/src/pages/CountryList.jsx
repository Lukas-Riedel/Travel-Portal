import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { useCategories } from "../hooks/useCategories"
import CategoryTileGrid from "../components/CategoryTileGrid.jsx"
import { useMemo } from "react"
import PlaceMap from "../components/PlaceMap.jsx"
import StatisticsPanel from "../components/StatisticsPanel.jsx"
import { useStatistics } from "../hooks/useStatistics"
import { useAuth } from "../contexts/AuthContext.jsx"
import { getMaxEndTimestamp } from "../utils/helpers.js"

export default function CountryList() {
    const { isAdmin } = useAuth()
    const places = useRegularPlaces({ maxEnd: getMaxEndTimestamp(isAdmin()), include: "CATEGORIES", sort: "score" })
    const countryCategories = useCategories({ categories: "COUNTRY" })
    const statistics = useStatistics()

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories.map(category => [category.name, category]))
    }, [countryCategories])

    const countries = useMemo(() => {
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

    if (places.length === 0 || countries.length === 0 || statistics.length === 0) {
        return null
    }

    return (
        <>
            <div className="h-[400px] md:h-[700px]">
                <PlaceMap
                    places={places}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)}
                />
            </div>
            <StatisticsPanel statistics={statistics} />
            <CategoryTileGrid categories={countries} />
        </>
    )
}
