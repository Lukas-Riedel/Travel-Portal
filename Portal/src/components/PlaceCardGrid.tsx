import { useMemo } from "react"
import type { DistanceAwarePlace } from "../classes/DistanceAwarePlace.ts"
import type { Place } from "../classes/Place.ts"
import CardGrid from "./CardGrid.tsx"
import PlaceCard from "./PlaceCard.tsx"

interface PlaceCardProps {
    places: Place[] | DistanceAwarePlace[] | null
    rowSize: number
    onPlaceRemoved?: (placeId: string) => Promise<void>
}

const haveDistance = (places: DistanceAwarePlace[] | Place[]): places is DistanceAwarePlace[] => places.length > 0 && (places[0] as DistanceAwarePlace).distance !== undefined

export default function PlaceCardGrid({ places, rowSize, onPlaceRemoved }: PlaceCardProps) {
    const sortedPlaces = useMemo(() => places && (haveDistance(places) ? [...places].sort((a, b) => a.distance - b.distance) : places), [places])

    return (
        <CardGrid rowSize={rowSize}>
            {sortedPlaces?.map(place => (
                <PlaceCard
                    key={place.id}
                    place={place}
                    onPlaceRemoved={onPlaceRemoved ? () => onPlaceRemoved(place.id) : undefined} />
            ))}
        </CardGrid>
    )
}
