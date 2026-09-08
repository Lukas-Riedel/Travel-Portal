import { CircleArrowUp, SquarePen, Trash2 } from "lucide-react"
import { useMemo } from "react"
import { useTranslation } from "react-i18next"

import type { Trip } from "../classes/Trip.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import type { Task, TaskPriority } from "../types/CoreSwaggerTypes.ts"
import { formatTimestamp } from "../utils/timeUtils.ts"
import AppLink from "./AppLink.tsx"
import Card from "./Card.tsx"
import LoadingCard from "./LoadingCard.tsx"
import PropertyCardContent from "./PropertyCardContent.tsx"

interface TaskCardProps {
    task: Task | null
    trip: Trip | null
    onTaskDescriptionUpdated?: (taskId: string, newDescription: string) => Promise<Task>
    onTaskPriorityUpdated?: (taskId: string, newPriority: TaskPriority) => Promise<Task>
    onTaskRemoved?: (taskId: string) => Promise<void>
}

export default function TaskCard({ task, trip, onTaskDescriptionUpdated, onTaskPriorityUpdated, onTaskRemoved }: TaskCardProps) {
    const { t } = useTranslation()
    const { showRemoveTaskToast, showUpdateTaskPriorityToast, showUpdateTaskDescriptionToast } = usePredefinedUserInput()

    const handleDelete = () => {
        if (onTaskRemoved && task) {
            showRemoveTaskToast(() => onTaskRemoved(task.id))
        }
    }

    const handleTaskDescriptionUpdate = () => {
        if (onTaskDescriptionUpdated && task) {
            showUpdateTaskDescriptionToast(task.description, description => onTaskDescriptionUpdated(task.id, description))
        }
    }

    const handleTaskPriorityUpdate = () => {
        if (onTaskPriorityUpdated && task) {
            showUpdateTaskPriorityToast(priority => onTaskPriorityUpdated(task.id, priority))
        }
    }

    const properties = useMemo(() => trip && task && ({
        [t("task.label.description")]: task.description,
        [t("task.label.deadline")]: task.deadline && formatTimestamp(task.deadline, t("general.format.date.year.included"))
    }), [trip, task, t])

    if (!task || !trip) {
        return (
            <LoadingCard />
        )
    }

    return (
        <Card>
            <div className="flex justify-start items-center">
                <AppLink
                    to={trip}
                    className="text-lg font-semibold hover:underline">
                    {trip.getFullName()}
                </AppLink>
                {!!(onTaskDescriptionUpdated || onTaskPriorityUpdated || onTaskRemoved) && (
                    <ul className="flex justify-end gap-1 ml-auto">
                        {onTaskPriorityUpdated && (
                            <li>
                                <button
                                    onClick={handleTaskPriorityUpdate}
                                    className="p-1 rounded text-orange-600 hover:bg-gray-100 transition-colors">
                                    <CircleArrowUp size={16} />
                                </button>
                            </li>
                        )}
                        {onTaskDescriptionUpdated && (
                            <li>
                                <button
                                    onClick={handleTaskDescriptionUpdate}
                                    className="p-1 rounded text-orange-600 hover:bg-gray-100 transition-colors">
                                    <SquarePen size={16} />
                                </button>
                            </li>
                        )}
                        {onTaskRemoved && (
                            <li>
                                <button
                                    onClick={handleDelete}
                                    className="p-1 rounded text-red-800 hover:bg-gray-100 transition-colors">
                                    <Trash2 size={16} />
                                </button>
                            </li>
                        )}
                    </ul>
                )}
            </div>
            <PropertyCardContent properties={properties} />
        </Card>
    )
}