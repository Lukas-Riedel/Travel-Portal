import TileGrid from "./TileGrid.jsx"
import PlaceTile from "./PlaceTile.jsx"

export default function PlaceTileGrid({ places, placeMainCategorySelector }) {
    return (
        <TileGrid>
            {places?.filter(place => place?.mainHighlight)?.map((place, index) => (
                <PlaceTile
                    key={index}
                    place={place}
                    mainCategory={placeMainCategorySelector(place)} />
            ))}
        </TileGrid>
    )
}