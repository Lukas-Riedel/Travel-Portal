import type { Trip } from "../classes/Trip.ts"
import type { Task, TaskPriority } from "./CoreSwaggerTypes.ts"

export interface UseRegularTripsResult {
    trips?: Trip[]
    createTripTask: (tripId: string, description: string, priority: TaskPriority, deadline?: number) => Promise<Task>
    updateTripTaskDescription?: (tripId: string, taskId: string, newDescription: string) => Promise<Task>
    updateTripTaskPriority?: (tripId: string, taskId: string, newPriority: TaskPriority) => Promise<Task>
    removeTripTask?: (tripId: string, taskId: string) => Promise<void>
}