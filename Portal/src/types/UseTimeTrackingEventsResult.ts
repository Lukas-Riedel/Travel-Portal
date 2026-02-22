import type { TimeTrackingEvent, TimeTrackingEventType } from "./CoreSwaggerTypes.ts";

export interface UseTimeTrackingEventsResult {
    timeTrackingEvents: Partial<Record<TimeTrackingEventType, TimeTrackingEvent[]>>
    createTimeTrackingEvent: (type: TimeTrackingEventType, description: string, hours: number, timestamp: number) => Promise<void>
    removeTimeTrackingEvent: (eventId: string) => Promise<void>
}