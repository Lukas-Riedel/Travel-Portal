import type { Event } from "./Event.ts"
import type { EventType } from "./EventType.ts"

type ChannelEvent<T extends EventType> =
    T extends EventType.ProcessingStarted | EventType.ProcessingEnded | EventType.ProcessingFailed
        ? Extract<Event, { name: EventType.PhotosUploadingTriggered | EventType.PhotoReplacingTriggered }>
        : Extract<Event, { name: T }>

export interface UseEventsResult<T extends EventType = EventType> {
    events: ChannelEvent<T>[] | null
    publishPhotosUploadingTriggeredEvent: (agentId: string, placeId: string, placeName: string, path: string, sendNotification: boolean, albumId?: string, timestamp?: number, mainPhotoPosition?: number) => Promise<void>
    publishPhotoReplacingTriggeredEvent: (agentId: string, placeId: string, albumId: string, placeName: string, replacedPhotoId: string, path: string, sendNotification?: boolean) => Promise<void>
    publishFolderSynchronizationRequestedEvent: (agentId: string, path: string, expiration: number) => Promise<void>
    publishAllAlbumsInvalidatedEvent: () => Promise<void>
}
