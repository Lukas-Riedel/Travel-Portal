import { useCallback, useMemo, useState } from "react"
import { useNotifications } from "../contexts/NotificationContext.jsx"
import { createEvent } from "../clients/coreClient.ts"
import { EventType } from "../types/EventType.ts"
import type { UseEventsResult } from "../types/UseEventsResult.ts"
import type { HighlightType } from "../types/CoreSwaggerTypes.ts"

export const useEvents = (eventType: EventType): UseEventsResult => {
    const { messages } = useNotifications()

    const [readMessageIds, setReadMessageIds] = useState(() => new Set<string>())

    const markAsRead = useCallback((messageId: string) => {
        setReadMessageIds(previous => new Set(previous).add(messageId))
    }, [setReadMessageIds])

    const events = useMemo(() => messages
        ?.filter(message => message.data?.event === eventType && !readMessageIds.has(message.messageId))
        ?.map(message => ({ ...(message.data?.args ?? {}), markAsRead: () => markAsRead(message.messageId) })), [messages, readMessageIds])

    return {
        events,
        publishHighlightsSelectingTriggeredEvent: (highlightType: HighlightType, entityId: string, highlightsCount: number, highlightsRemovalAllowed: boolean) =>
            createEvent(EventType.HighlightsSelectingTriggered, { highlightType, entityId, highlightsCount, highlightsRemovalAllowed }),
        publishPhotosUploadingTriggeredEvent: (agentId: string, placeId: string, placeName: string, path: string, albumId?: string, timestamp?: number, mainPhotoPosition?: number) =>
            createEvent(EventType.PhotosUploadingTriggered, { agentId, placeId, placeName, path, albumId, timestamp, mainPhotoPosition }),
        publishPhotoReplacingTriggeredEvent: (agentId: string, placeId: string, albumId: string, placeName: string, replacedPhotoId: string, path: string) =>
            createEvent(EventType.PhotoReplacingTriggered, { agentId, placeId, placeName, albumId, replacedPhotoId, path }),
        publishFolderSynchronizationRequestedEvent: (agentId: string, path: string, expiration: number) =>
            createEvent(EventType.FolderSynchronizationRequested, { agentId, path, expiration }),
        publishAllAlbumsInvalidatedEvent: () => createEvent(EventType.AllAlbumsInvalidated)
    }
}