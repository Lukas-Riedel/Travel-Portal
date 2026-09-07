import type { EventType } from "./EventType.ts"

interface EventBase {
    markAsRead: () => void
}

interface PhotosUploadingTriggeredEvent extends EventBase {
    name: EventType.PhotosUploadingTriggered
    args: { agentId: string; placeId: string; placeName: string; path: string; sendNotification: boolean; albumId?: string; timestamp?: number; mainPhotoPosition?: number; result?: string }
}

interface PhotoReplacingTriggeredEvent extends EventBase {
    name: EventType.PhotoReplacingTriggered
    args: { agentId: string; placeId: string; albumId: string; placeName: string; replacedPhotoId: string; path: string; sendNotification?: boolean }
}

interface NewDataConsistencyIssuesDetectedEvent extends EventBase {
    name: EventType.NewDataConsistencyIssuesDetected
    args: { count: number }
}

interface TaskDeadlineReachedEvent extends EventBase {
    name: EventType.TaskDeadlineReached
    args: { task: string }
}

interface FlightLoggedEvent extends EventBase {
    name: EventType.FlightLogged
    args: { flight: string; to: string; actualArrival: number }
}

interface FlightReminderReceivedEvent extends EventBase {
    name: EventType.FlightReminderReceived
    args: { text: string }
}

interface AllAlbumsInvalidatedEvent extends EventBase {
    name: EventType.AllAlbumsInvalidated
    args: Record<string, never>
}

interface FolderSynchronizationRequestedEvent extends EventBase {
    name: EventType.FolderSynchronizationRequested
    args: Record<string, never>
}

export type Event =
    | PhotosUploadingTriggeredEvent
    | PhotoReplacingTriggeredEvent
    | NewDataConsistencyIssuesDetectedEvent
    | TaskDeadlineReachedEvent
    | FlightLoggedEvent
    | FlightReminderReceivedEvent
    | AllAlbumsInvalidatedEvent
    | FolderSynchronizationRequestedEvent
