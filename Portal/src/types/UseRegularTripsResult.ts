import type { Task, TaskPriority, Trip } from "./CoreSwaggerTypes.ts"

export interface UseRegularTripsResult {
    trips: Trip[] | null
    createTripTask: (tripId: string, title: string, priority: TaskPriority, notificationInterval?: number, deadline?: number, description?: string, autoDelete?: boolean) => Promise<Task>
    updateTripTaskTitle?: (tripId: string, taskId: string, newTitle: string, newDescription?: string) => Promise<Task>
    updateTripTaskPriority?: (tripId: string, taskId: string, newPriority: TaskPriority) => Promise<Task>
    removeTripTask?: (tripId: string, taskId: string) => Promise<void>
}