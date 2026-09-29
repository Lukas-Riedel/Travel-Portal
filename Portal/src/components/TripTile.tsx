import { useTranslation } from "react-i18next"

import { useConfiguration } from "../contexts/ConfigContext"
import { useCategories } from "../hooks/useCategories"
import type { Trip } from "../types/CoreSwaggerTypes"
import { CategoryCategory } from "../types/CoreSwaggerTypes"
import { formatDateRange } from "../utils/timeUtils"
import { getTripFullName } from "../utils/tripUtils"
import PhotoTile from "./PhotoTile"

interface TripTileProps {
    trip: Trip
}

export default function TripTile({ trip }: TripTileProps) {
    const { t } = useTranslation()
    const { configuration } = useConfiguration();

    const countryCategories = useCategories({ categories: [CategoryCategory.Country] })
    const categories = countryCategories?.filter(category => trip.countries?.some(country => country === category.name))?.sort((a, b) => a.name.localeCompare(b.name))

    return (
        <PhotoTile
            src={trip.mainHighlight?.url?.thumbnail ?? trip.mainHighlight?.url?.full ?? null}
            firstLineText={getTripFullName(trip)}
            secondLineText={formatDateRange(trip.start!, trip.end! - 1, t("general.format.date.year.included"), configuration?.homeLocation?.timezone)}
            categories={categories}
            to={trip} />
    )
}