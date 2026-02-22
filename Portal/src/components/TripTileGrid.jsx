import TileGrid from "./TileGrid.jsx"
import TripTile from "./TripTile.jsx"

export default function TripTileGrid({ trips }) {
    return (
        <TileGrid>
            {trips?.filter(trip => trip?.mainHighlight)?.map(trip => (
                <TripTile
                    key={trip.id}
                    trip={trip} />
            ))}
        </TileGrid>
    )
}