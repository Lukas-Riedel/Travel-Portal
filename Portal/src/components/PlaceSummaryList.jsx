import PlaceSummary from "../pages/PlaceSummary"

const loadingPlacesCount = 3

export default function PlaceSummaryList({ places }) {
    return places ? (
        places?.filter(place => place.dates)?.map(place => (
            <PlaceSummary
                key={place.id}
                place={place} />
        ))
    ) : Array.from({ length: loadingPlacesCount }, (_, index) => (
        <PlaceSummary key={index} />
    ))
}
