import type { Trip } from "../classes/Trip.ts"
import { type Task, TaskPriority } from "../types/CoreSwaggerTypes.ts"
import { Signal, SignalHigh, SignalMedium, SignalLow, SignalZero, Flame, ChevronUp, Circle, ChevronDown, ArrowDown, ArrowUp, Minus } from "lucide-react"
import CardGrid from "./CardGrid.tsx"
import TaskCard from "./TaskCard.tsx"
import { useTranslation } from "react-i18next"
import { useMemo } from "react"

interface TaskCardBoardProps {
    tasksWithTrips: { task: Task, trip: Trip }[] | null
    onTaskDescriptionUpdated?: (tripId: string, taskId: string, newDescription: string) => Promise<Task>
    onTaskPriorityUpdated?: (tripId: string, taskId: string, newPriority: TaskPriority) => Promise<Task>
    onTaskRemoved?: (tripId: string, taskId: string) => Promise<void>
}

const PRIORITY_THEME: Record<TaskPriority, { color: string; icon: React.ComponentType<{ className?: string, size?: number }> }> = {
    [TaskPriority.Highest]: { color: "text-red-600", icon: ArrowUp },
    [TaskPriority.High]: { color: "text-orange-500", icon: ChevronUp },
    [TaskPriority.Medium]: { color: "text-amber-500", icon: Minus },
    [TaskPriority.Low]: { color: "text-blue-500", icon: ChevronDown },
    [TaskPriority.Lowest]: { color: "text-green-400", icon: ArrowDown },
}

const PRIORITY_ORDER: TaskPriority[] = [
    TaskPriority.Highest,
    TaskPriority.High,
    TaskPriority.Medium,
    TaskPriority.Low,
    TaskPriority.Lowest
]

export default function TaskCardBoard({ tasksWithTrips, onTaskDescriptionUpdated, onTaskPriorityUpdated, onTaskRemoved }: TaskCardBoardProps) {
    const { t } = useTranslation()

    const sortedTasks = useMemo(() => {
        if (!tasksWithTrips) {
            return []
        }

        return [...tasksWithTrips].sort((a, b) => {
            if (!a.task.deadline) {
                return 1
            }
            if (!b.task.deadline) {
                return -1
            }
            return new Date(a.task.deadline).getTime() - new Date(b.task.deadline).getTime()
        })
    }, [tasksWithTrips])

    const groups = useMemo(() => {
        return Object.groupBy(sortedTasks, item => item.task.priority)
    }, [sortedTasks])


    return (
        <div
            className="grid grid-cols-1 md:grid-cols-5 gap-4 text-sm w-full my-6 items-stretch">
            {PRIORITY_ORDER.map(priority => {
                if (!groups[priority]) {
                    return null
                }

                const { color, icon: Icon } = PRIORITY_THEME[priority]
                return (
                    <div
                        key={priority}
                        className="flex flex-col h-full">
                        <div className="flex items-center gap-2 px-1 mb-3">
                            <Icon
                                className={color}
                                size={22}
                            />
                            <h2 className="text-base font-bold text-gray-800 dark:text-gray-200">
                                {t(`task.priority.${priority}`)}
                            </h2>
                            <span className="ml-auto text-xs bg-gray-200/60 dark:bg-gray-800 text-gray-600 dark:text-gray-400 font-medium px-2 py-0.5 rounded-full">
                                {groups[priority].length}
                            </span>
                        </div>
                        <CardGrid
                            rowSize={1}
                            className="mb-6 flex-1">
                            {groups[priority].map(({ task, trip }) => (
                                <TaskCard
                                    key={task.id}
                                    task={task}
                                    trip={trip}
                                    onTaskDescriptionUpdated={(taskId, newDescription) => onTaskDescriptionUpdated(trip.id, taskId, newDescription)}
                                    onTaskPriorityUpdated={(taskId, newPriority) => onTaskPriorityUpdated(trip.id, taskId, newPriority)}
                                    onTaskRemoved={taskId => onTaskRemoved(trip.id, taskId)} />
                            ))}
                        </CardGrid>
                    </div>
                )
            })}
        </div>
    )
}
