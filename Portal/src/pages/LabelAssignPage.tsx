import { useState } from "react"
import { useParams } from "react-router-dom"
import { toast } from "sonner"

import { createPlaceLabel, removePlaceLabel } from "../clients/coreClient.ts"
import PageHeader from "../components/PageHeader.tsx"
import PhotoTile from "../components/PhotoTile.tsx"
import TileGrid from "../components/TileGrid.tsx"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useLabel } from "../hooks/useLabel.ts"
import { useRegularPlaces } from "../hooks/useRegularPlaces.ts"
import { CategoryCategory, type Place, PlaceIncludedEntity, type TripIdentifier, UserRole } from "../types/CoreSwaggerTypes.ts"
import { getPlaceCategory } from "../utils/placeUtils.ts"

const NO_TRIP_GROUP_ID = ""

const getFirstTripDate = (place: Place) => [...(place.dates ?? [])].sort((a, b) => a.start - b.start).find(date => date.trip)

// TODO: Temporary page for manual label assignment. Remove it once all labels are assigned.
export default function LabelAssignPage() {
    const { labelId } = useParams()
    const { hasRole } = useAuth()

    const { label } = useLabel(labelId)
    const { places: regularPlaces } = useRegularPlaces({ include: [PlaceIncludedEntity.Categories, PlaceIncludedEntity.Dates] })
    const { places: labelPlaces } = useRegularPlaces({ labelId })

    const [assignmentOverrides, setAssignmentOverrides] = useState<Map<string, boolean>>(new Map())

    const isReady = label && regularPlaces && labelPlaces
    const labeledPlaceIds = new Set(labelPlaces?.map(place => place.id))

    const groups = new Map<string, { trip: TripIdentifier | null, places: Place[] }>()
    if (isReady) {
        const placesWithTrip = regularPlaces.filter(place => place.mainHighlight)
            .map(place => ({ place, tripDate: getFirstTripDate(place) }))
            .sort((a, b) => (a.tripDate?.start ?? 0) - (b.tripDate?.start ?? 0))
        for (const { place, tripDate } of placesWithTrip) {
            const groupId = tripDate?.trip?.id ?? NO_TRIP_GROUP_ID
            if (!groups.has(groupId)) {
                groups.set(groupId, { trip: tripDate?.trip ?? null, places: [] })
            }
            groups.get(groupId)!.places.push(place)
        }
    }

    const handlePlaceClicked = async (place: Place, isAssigned: boolean) => (isAssigned ? removePlaceLabel(place.id, label!.id) : createPlaceLabel(place.id, label!.name))
        .then(() => {
            setAssignmentOverrides(previous => new Map(previous).set(place.id, !isAssigned))
            toast.success(`${place.name} ${isAssigned ? "✕" : "→"} ${label!.name}`)
        })

    return hasRole(UserRole.PlaceLabelEdit) && (
        <>
            <PageHeader name={label?.name ?? null} />
            {!isReady && <TileGrid />}
            {[...groups.entries()].map(([groupId, group]) => (
                <div key={groupId}>
                    <h2 className="mt-6 text-xl font-semibold">{group.trip?.name ?? ""} {group.trip?.year ?? ""}</h2>
                    <TileGrid>
                        {group.places.map(place => {
                            const isAssigned = assignmentOverrides.get(place.id) ?? labeledPlaceIds.has(place.id)
                            const country = getPlaceCategory(place, CategoryCategory.Country)
                            return (
                                <PhotoTile
                                    key={place.id}
                                    src={place.mainHighlight?.url?.thumbnail ?? place.mainHighlight?.url?.full ?? null}
                                    firstLineText={place.name}
                                    categories={country ? [country] : undefined}
                                    onClick={() => handlePlaceClicked(place, isAssigned)}
                                    className={isAssigned ? "w-[350px] h-[233px] opacity-30" : undefined} />
                            )
                        })}
                    </TileGrid>
                </div>
            ))}
        </>
    )
}
