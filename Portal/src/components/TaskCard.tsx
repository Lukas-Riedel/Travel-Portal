import { CircleArrowUp, SquarePen, Trash2 } from "lucide-react"
import { useTranslation } from "react-i18next"

import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import type { Trip } from "../types/CoreSwaggerTypes.ts"
import type { Task, TaskPriority } from "../types/CoreSwaggerTypes.ts"
import { formatTimestamp } from "../utils/timeUtils.ts"
import { getTripFullName } from "../utils/tripUtils.ts"
import AppLink from "./AppLink.tsx"
import Card from "./Card.tsx"
import LoadingCard from "./LoadingCard.tsx"
import PropertyCardContent from "./PropertyCardContent.tsx"

interface TaskCardProps {
    task: Task | null
    trip: Trip | null
    onTaskTitleAndDescriptionUpdated?: (taskId: string, newTitle: string, newDescription?: string) => Promise<Task>
    onTaskPriorityUpdated?: (taskId: string, newPriority: TaskPriority) => Promise<Task>
    onTaskRemoved?: (taskId: string) => Promise<void>
}

export default function TaskCard({ task, trip, onTaskTitleAndDescriptionUpdated, onTaskPriorityUpdated, onTaskRemoved }: TaskCardProps) {
    const { t } = useTranslation()
    const { showRemoveTaskToast, showUpdateTaskPriorityToast, showUpdateTaskTitleAndDescriptionToast } = usePredefinedUserInput()

    const handleTaskRemoved = () => {
        if (onTaskRemoved && task && task.id) {
            showRemoveTaskToast(() => onTaskRemoved(task.id!))
        }
    }

    const handleTaskDescriptionUpdated = () => {
        if (onTaskTitleAndDescriptionUpdated && task && task.id) {
            showUpdateTaskTitleAndDescriptionToast(task.title, task.description, (title: string, description?: string) => onTaskTitleAndDescriptionUpdated(task.id!, title, description))
        }
    }

    const handleTaskPriorityUpdated = () => {
        if (onTaskPriorityUpdated && task && task.id) {
            showUpdateTaskPriorityToast(priority => onTaskPriorityUpdated(task.id!, priority))
        }
    }

    const properties = trip && task && ({
        [t("task.label.trip")]: getTripFullName(trip),
        [t("task.label.description")]: task.description,
        [t("task.label.deadline")]: task.deadline && formatTimestamp(task.deadline, t("general.format.date.year.included"))
    })

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
                    {task.title}
                </AppLink>
                {!!(onTaskTitleAndDescriptionUpdated || onTaskPriorityUpdated || onTaskRemoved) && (
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
                        {onTaskTitleAndDescriptionUpdated && (
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