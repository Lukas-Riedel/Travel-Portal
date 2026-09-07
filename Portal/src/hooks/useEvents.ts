import { useMemo, useState } from "react"

import { createEvent } from "../clients/coreClient.ts"
import { useNotifications } from "../contexts/NotificationContext.jsx"
import { EventType } from "../types/EventType.ts"
import type { UseEventsResult } from "../types/UseEventsResult.ts"

export const useEvents = <T extends EventType>(eventType?: T): UseEventsResult<T> => {
    const { messages } = useNotifications()

    const [readMessageIds, setReadMessageIds] = useState(() => new Set<string>())

    const markAsRead = (messageId: string) => {
        setReadMessageIds(previous => new Set(previous).add(messageId))
    }

    const events = useMemo(() => messages
        ?.filter(message => message.data?.event === eventType && !readMessageIds.has(message.messageId))
        ?.map(message => ({ ...(message.data?.args as Record<string, unknown> ?? {}), markAsRead: () => markAsRead(message.messageId) }) as NonNullable<UseEventsResult<T>["events"]>[number]), [messages, readMessageIds])

    return {
        events,
        publishPhotosUploadingTriggeredEvent: (agentId: string, placeId: string, placeName: string, path: string, sendNotification: boolean, albumId?: string, timestamp?: number, mainPhotoPosition?: number) =>
            createEvent(EventType.PhotosUploadingTriggered, { agentId, placeId, placeName, path, sendNotification, albumId, timestamp, mainPhotoPosition }),
        publishPhotoReplacingTriggeredEvent: (agentId: string, placeId: string, albumId: string, placeName: string, replacedPhotoId: string, path: string, sendNotification?: boolean) =>
            createEvent(EventType.PhotoReplacingTriggered, { agentId, placeId, placeName, albumId, replacedPhotoId, path, sendNotification }),
        publishFolderSynchronizationRequestedEvent: (agentId: string, path: string, expiration: number) =>
            createEvent(EventType.FolderSynchronizationRequested, { agentId, path, expiration }),
        publishAllAlbumsInvalidatedEvent: () => createEvent(EventType.AllAlbumsInvalidated)
    }
}
