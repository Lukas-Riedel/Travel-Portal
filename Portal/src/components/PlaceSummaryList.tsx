import type { Place } from "../classes/Place"
import PlaceSummary from "./PlaceSummary"

const LOADING_PLACES_COUNT = 3

interface PlaceSummaryListProps {
    places: Place[] | null
}

export default function PlaceSummaryList({ places }: PlaceSummaryListProps) {
    return places ? (
        places.filter(place => place.dates).map(place => (
            <PlaceSummary
                key={place.id}
                place={place} />
        ))
    ) : Array.from({ length: LOADING_PLACES_COUNT }, (_, index) => (
        <PlaceSummary
            key={index}
            place={null} />
    ))
}
