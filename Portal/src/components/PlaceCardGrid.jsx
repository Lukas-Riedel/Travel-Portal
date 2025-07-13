import PlaceCard from "./PlaceCard"

const loadingPlacesCount = 5

export default function PlaceCardGrid({ places, onPlaceRemoved }) {
    return (
        // TODO: There are many card grids that are the same. Introduce common CardGrid similar to TileGrid.
        <div className="relative w-full my-4">
            <div className="grid grid-cols-[repeat(auto-fill,minmax(11rem,1fr))] gap-4 text-sm w-full">
                {places?.sort((a, b) => a.distance - b.distance)?.map(place => (
                    <PlaceCard
                        key={place.id}
                        place={place}
                        onPlaceRemoved={onPlaceRemoved} />
                )) ?? Array.from({ length: loadingPlacesCount }).map((_, index) => (
                    <PlaceCard key={index} />
                ))}
            </div>
        </div>
    )
}
