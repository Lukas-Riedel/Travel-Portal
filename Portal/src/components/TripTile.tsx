import { useTranslation } from "react-i18next"

import type { Trip } from "../types/CoreSwaggerTypes"
import { useCategories } from "../hooks/useCategories"
import { CategoryCategory } from "../types/CoreSwaggerTypes"
import { formatDateRange } from "../utils/timeUtils"
import { getTripFullName } from "../utils/tripUtils"
import PhotoTile from "./PhotoTile"

interface TripTileProps {
    trip: Trip
}

export default function TripTile({ trip }: TripTileProps) {
    const { t } = useTranslation()

    const countryCategories = useCategories({ categories: [CategoryCategory.Country] })
    const categories = countryCategories?.filter(category => trip.countries?.some(country => country === category.name))?.sort((a, b) => a.name.localeCompare(b.name))

    return (
        <PhotoTile
            src={trip.mainHighlight?.url?.thumbnail ?? trip.mainHighlight?.url?.full ?? null}
            firstLineText={getTripFullName(trip)}
            secondLineText={formatDateRange(trip.start!, trip.end!, t("general.format.date.year.included"))}
            categories={categories}
            to={trip} />
    )
}