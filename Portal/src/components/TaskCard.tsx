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

    const handleTaskRemoved = () => {
        if (onTaskRemoved && task) {
            showRemoveTaskToast(() => onTaskRemoved(task.id))
        }
    }

    const handleTaskDescriptionUpdated = () => {
        if (onTaskDescriptionUpdated && task) {
            showUpdateTaskDescriptionToast(task.description, description => onTaskDescriptionUpdated(task.id, description))
        }
    }

    const handleTaskPriorityUpdated = () => {
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
                                    onClick={handleTaskPriorityUpdated}
                                    className="btn-ghost-warning">
                                    <CircleArrowUp size={16} />
                                </button>
                            </li>
                        )}
                        {onTaskDescriptionUpdated && (
                            <li>
                                <button
                                    onClick={handleTaskDescriptionUpdated}
                                    className="btn-ghost-warning">
                                    <SquarePen size={16} />
                                </button>
                            </li>
                        )}
                        {onTaskRemoved && (
                            <li>
                                <button
                                    onClick={handleTaskRemoved}
                                    className="btn-ghost-danger">
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