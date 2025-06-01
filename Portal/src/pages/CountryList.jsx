import { useRegularPlaces } from "../hooks/useRegularPlaces.js"
import { useCountryCategories } from "../hooks/useCountryCategories.js"
import CategoryTileGrid from "../components/CategoryTileGrid.jsx"
import { useMemo } from "react"
import PlaceMap from "../components/PlaceMap.jsx"
import StatisticsPanel from "../components/StatisticsPanel.jsx"
import { useOverallStatistics } from "../hooks/useOverallStatistics.js"

export default function CountryList() {
    const { data: allPlaces = [] } = useRegularPlaces()
    const { data: countryCategories = [] } = useCountryCategories()
    const { data: statistics = [] } = useOverallStatistics()

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories.map(category => [category.name, category]))
    }, [countryCategories])

    const countries = useMemo(() => {
        if (allPlaces.length === 0 || countryCategories.length === 0) {
            return []
        }

        const scoreByCountry = allPlaces.reduce((acc, place) => {
            acc[place.country] = (acc[place.country] || 0) + place.score
            return acc
        }, {})

        return Object.entries(scoreByCountry)
            .sort(([, scoreA], [, scoreB]) => scoreB - scoreA)
            .map(([country]) => countryCategoriesMap.get(country))
            .filter(Boolean)
    }, [allPlaces, countryCategoriesMap, countryCategories.length])

    if (allPlaces.length === 0 || countries.length === 0 || statistics.length === 0) {
        return null
    }

    return (
        <>
            <div className="h-[700px]">
                <PlaceMap
                    places={allPlaces}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)}
                />
            </div>
            <StatisticsPanel statistics={statistics} />
            <CategoryTileGrid categories={countries} />
        </>
    )
}
