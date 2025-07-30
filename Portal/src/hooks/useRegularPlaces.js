import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"
import Place from "../model/place"
import { useEvents } from "./useEvents"
import { useEffect, useMemo } from "react"

export const useRegularPlaces = ({ tripId, categoryId, labelId, year, albumId, photoId, minStart, maxEnd, include, sort } = {}) => {
    const { listRegularPlaces } = useApi()
    const { isAdmin } = useAuth()
    const { events: processingStartedEvents } = useEvents("ProcessingStarted")
    const { events: processingEndedEvents } = useEvents("ProcessingEnded")

    const uploadedDates = useMemo(() => new Set(processingEndedEvents?.filter(event => event.name === "PhotosUploadingTriggered")?.map(event => event.args.timestamp) ?? []), [processingEndedEvents])
    // TODO: This won't work for repeated uploads for the same date.
    const datesBeingUploaded = useMemo(() => new Set(processingStartedEvents?.filter(event => event.name === "PhotosUploadingTriggered")?.filter(event => !uploadedDates.has(event.args.timestamp))
        ?.map(event => event.args.timestamp) ?? []), [uploadedDates, processingStartedEvents])

    const validity = 60 * 60 * 2
    const query = useQuery({
        queryKey: ["listRegularPlaces", tripId, categoryId, labelId, year, albumId, photoId, minStart - (minStart % validity), maxEnd - (maxEnd % validity), include, sort],
        queryFn: () => listRegularPlaces({ tripId, categoryId, labelId, year, albumId, photoId, minStart, maxEnd, include, sort }),
        staleTime: isAdmin ? 0 : 1000 * validity,
        refetchInterval: query => isAdmin && query.state.data?.flatMap(place => place.dates)?.some(date => (date.album?.uploadingStart && date.album?.uploadingProgress) || datesBeingUploaded.has(timestamp => timestamp == date.start)) && 10000
    })

    useEffect(() => {
        if (query.data) {
            query.refetch()
        }
    }, [processingStartedEvents])

    return query.data && query.data.map(place => new Place(place))
}