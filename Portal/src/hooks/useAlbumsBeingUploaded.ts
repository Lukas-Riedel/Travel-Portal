import { useMemo } from "react"

import type { Date } from "../types/CoreSwaggerTypes.ts"
import { EventType } from "../types/EventType.ts"
import type { UseAlbumsBeingUploadedResult } from "../types/UseAlbumsBeingUploadedResult.ts"
import { useEvents } from "./useEvents.ts"

export const useAlbumsBeingUploaded = (): UseAlbumsBeingUploadedResult => {
    const { events: processingStartedEvents } = useEvents(EventType.ProcessingStarted)
    const { events: processingEndedEvents } = useEvents(EventType.ProcessingEnded)
    const { events: processingFailedEvents } = useEvents(EventType.ProcessingFailed)

    const uploadedTimestamps = useMemo<Set<number>>(() => new Set([...(processingEndedEvents ?? []), ...(processingFailedEvents ?? [])]
        .filter(event => event.name === EventType.PhotosUploadingTriggered).map(event => event.args.timestamp ?? 0)), [processingEndedEvents, processingFailedEvents])
    // TODO: This won't work for repeated uploads for the same date.
    const timestampsBeingUploaded = useMemo<Set<number>>(() => new Set(processingStartedEvents?.filter(event => event.name === EventType.PhotosUploadingTriggered)?.filter(event => !uploadedTimestamps.has(event.args.timestamp ?? 0))
        ?.map(event => event.args.timestamp ?? 0) ?? []), [uploadedTimestamps, processingStartedEvents])

    return {
        startedUploadingsCount: processingStartedEvents?.length ?? 0,
        isBeingUploaded: (date: Date) => timestampsBeingUploaded.has(date.start)
    }
}