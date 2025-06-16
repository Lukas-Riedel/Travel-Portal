import { useMemo } from "react"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { getDateRangeString } from "../utils/helpers"
import PhotoTile from "./PhotoTile"

export default function TripTile({ trip }) {
    const tripPlaces = useRegularPlaces({ tripId: trip?.id, include: "CATEGORIES,DATES" })
    const categories = useMemo(() => [...new Map(tripPlaces?.filter(place => !place.dates?.some(date => date?.layover))
        ?.map(place => place.getCategory("COUNTRY"))?.filter(category => category)?.map(category => [category.name, category])).values()]
        ?.sort((a, b) => a.name.localeCompare(b.name)), [tripPlaces])

    return (
        <PhotoTile
            src={trip?.mainHighlight?.url?.thumbnail ?? trip?.mainHighlight?.url?.full}
            firstLineText={!trip?.isDayTrips() ? trip?.name : trip?.getFullName()}
            secondLineText={!trip?.isDayTrips() && getDateRangeString(trip?.start, trip?.end)}
            categories={categories}
            to={"/trip/" + trip?.id} />
    )
}