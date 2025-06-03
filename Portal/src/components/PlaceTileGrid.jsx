import TileGrid from "./TileGrid.jsx"
import PlaceTile from "./PlaceTile.jsx"

export default function PlaceTileGrid({ places, placeMainCategorySelector }) {
    return (
        <TileGrid tiles={places?.map((place, index) => (
            <PlaceTile
                key={index}
                place={place}
                mainCategory={placeMainCategorySelector(place)} />
        ))} />
    )
}