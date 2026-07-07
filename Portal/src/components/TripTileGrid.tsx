import type { Trip } from "../classes/Trip"
import TileGrid from "./TileGrid"
import TripTile from "./TripTile"

interface TripTileGridProps {
    trips: Trip[] | null
}

export default function TripTileGrid({ trips }: TripTileGridProps) {
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