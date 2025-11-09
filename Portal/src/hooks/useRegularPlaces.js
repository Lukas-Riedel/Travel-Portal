import { useQuery } from "@tanstack/react-query"
import { useAuth } from "../contexts/AuthContext"
import { useEvents } from "./useEvents"
import { useEffect, useMemo } from "react"
import { createPermanentPlace, listRegularPlaces, removePermanentPlace } from "../clients/coreClient"
import { Place } from "../classes/Place.ts"

// TODO: This accepts string now, make it accept PlaceIncludedEntity[]
export const useRegularPlaces = ({ tripId, categoryId, labelId, year, albumId, photoId, minStart, maxEnd, limit, include, sort } = {}) => {
    const { isAdmin } = useAuth()
    const { events: processingStartedEvents } = useEvents("ProcessingStarted")
    const { events: processingEndedEvents } = useEvents("ProcessingEnded")
    const { events: processingFailedEvents } = useEvents("ProcessingFailed")

    const uploadedDates = useMemo(() => new Set([...(processingEndedEvents ?? []), ...(processingFailedEvents ?? [])].filter(event => event.name === "PhotosUploadingTriggered").map(event => event.args.timestamp)), [processingEndedEvents, processingFailedEvents])
    // TODO: This won't work for repeated uploads for the same date.
    const datesBeingUploaded = useMemo(() => new Set(processingStartedEvents?.filter(event => event.name === "PhotosUploadingTriggered")?.filter(event => !uploadedDates.has(event.args.timestamp))
        ?.map(event => event.args.timestamp) ?? []), [uploadedDates, processingStartedEvents])

    const validity = 60 * 60 * 2
    const query = useQuery({
        queryKey: ["listRegularPlaces", tripId, categoryId, labelId, year, albumId, photoId, minStart - (minStart % validity), maxEnd - (maxEnd % validity), limit, include, sort],
        queryFn: () => listRegularPlaces({ tripId, categoryId, labelId, year, albumId, photoId, minStart, maxEnd, limit, include: include?.split(","), sort }),
        staleTime: isAdmin ? 0 : 1000 * validity,
        refetchInterval: query => isAdmin && query.state.data?.flatMap(place => place.dates ?? [])?.some(date => (date.album?.uploadingStart && date.album?.uploadingProgress) || datesBeingUploaded.has(date.start)) && 10000
    })

    useEffect(() => {
        if (query.data) {
            query.refetch()
        }
    }, [processingStartedEvents])
    
    const refetchPlaces = _ => query.refetch()

    return {
        places: query.data && query.data.map(place => new Place(place)),
        createPermanentPlace: (name, address) => createPermanentPlace(name, address).then(refetchPlaces),
        removePermanentPlace: placeId => removePermanentPlace(placeId).then(refetchPlaces)
    }
}