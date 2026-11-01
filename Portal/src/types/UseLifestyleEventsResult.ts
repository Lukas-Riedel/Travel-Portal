import type { LifestyleEvent, LifestyleEventType } from "./CoreSwaggerTypes.ts"

export interface UseLifestyleEventsResult {
    events: LifestyleEvent[] | null
    createLifestyleEvent: (type: LifestyleEventType, content: string, timestamp: number, calories?: number) => Promise<LifestyleEvent>
    updateLifestyleEventContent: (eventId: string, content: string) => Promise<LifestyleEvent>
    removeLifestyleEvent: (eventId: string) => Promise<void>
}
