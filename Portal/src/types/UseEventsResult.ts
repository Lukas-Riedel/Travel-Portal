import type { Flight, Place, Date, PublicHoliday, Stay } from "./CoreSwaggerTypes.ts"
import type { Event } from "./Event.ts"

export interface UseEventsResult {
    events?: Event[]
    publishPhotosUploadingTriggeredEvent: (agentId: string, placeId: string, placeName: string, path: string, sendNotification: boolean, albumId?: string, timestamp?: number, mainPhotoPosition?: number) => Promise<void>
    publishPhotoReplacingTriggeredEvent: (agentId: string, placeId: string, albumId: string, placeName: string, replacedPhotoId: string, path: string, sendNotification?: boolean) => Promise<void>
    publishFolderSynchronizationRequestedEvent: (agentId: string, path: string, expiration: number) => Promise<void>
    publishAllAlbumsInvalidatedEvent: () => Promise<void>
    publishDayItinerarySharingRequestedEvent: (tripId: string, context: string, events: (Flight | (Place & Date))[], stay?: Stay, publicHoliday?: PublicHoliday) => Promise<void>
}