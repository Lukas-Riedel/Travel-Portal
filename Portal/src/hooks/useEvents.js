import { useCallback, useMemo, useState } from "react"
import { useNotifications } from "../contexts/NotificationContext"
import { useApi } from "./useApi"

export const useEvents = eventName => {
    const { createEvent } = useApi()
    const { messages } = useNotifications()

    const [readMessageIds, setReadMessageIds] = useState(() => new Set())

    const markAsRead = useCallback(messageId => {
        setReadMessageIds(prev => new Set(prev).add(messageId))
    }, [])

    const events = useMemo(() => messages
        ?.filter(msg => msg.data?.event === eventName && !readMessageIds.has(msg.messageId))
        ?.map(msg => ({ ...msg.data?.args, markAsRead: () => markAsRead(msg.messageId) })) ?? [], [messages, readMessageIds])

    return {
        events,
        publishPhotosUploadingTriggeredEvent: (agentId, placeId, placeName, albumId, timestamp, path, mainPhotoPosition) =>
            createEvent("PhotosUploadingTriggered", { agentId, placeId, placeName, albumId, timestamp, path, mainPhotoPosition }),
        publishPhotoReplacingTriggeredEvent: (agentId, placeId, albumId, placeName, replacedPhotoId, path) =>
            createEvent("PhotoReplacingTriggered", { agentId, placeId, placeName, albumId, replacedPhotoId, path }),
        publishAllAlbumsInvalidatedEvent: () => createEvent("AllAlbumsInvalidated", null)
    }
}