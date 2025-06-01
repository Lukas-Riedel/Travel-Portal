import { useRegularPlaces } from "../hooks/useRegularPlaces.js"
import { useCountryCategories } from "../hooks/useCountryCategories.js"
import CategoryTileGrid from "../components/CategoryTileGrid.jsx"
import { useEffect, useState } from "react"
import PlaceMap from "../components/PlaceMap.jsx"
import StatisticsPanel from "../components/StatisticsPanel.jsx"
import { useOverallStatistics } from "../hooks/useOverallStatistics.js"

export default function CountryList() {
    const { data: allPlaces = [] } = useRegularPlaces()
    const { data: countryCategories = [] } = useCountryCategories()
    const { data: statistics = [] } = useOverallStatistics()
    const [countries, setCountries] = useState([])

    const countryCategoriesMap = new Map(countryCategories.map(category => [category.name, category]))

    useEffect(() => {
        if (countryCategories.length === 0) {
            return
        }

        setCountries(Object.entries(allPlaces
            .reduce((acc, place) => {
                acc[place.country] = (acc[place.country] || 0) + place.score;
                return acc;
            }, {}))
            .sort(([, countA], [, countB]) => countB - countA)
            .map(([country]) => countryCategoriesMap.get(country)))

    }, [allPlaces, countryCategories])

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
