import { Activity, Flame, Footprints, type LucideIcon, Scale, Trash2, Utensils } from "lucide-react"
import { useTranslation } from "react-i18next"
import { TailSpin } from "react-loader-spinner"

import { useFormatters } from "../hooks/useFormatters.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import type { LifestyleEvent } from "../types/CoreSwaggerTypes.ts"
import { LifestyleEventType } from "../types/CoreSwaggerTypes.ts"
import { formatTimestamp } from "../utils/timeUtils.ts"

const EVENT_TYPES: Record<LifestyleEventType, { color: string, icon: LucideIcon, isExpenditure: boolean }> = {
    [LifestyleEventType.Intake]: { color: "text-red-600", icon: Utensils, isExpenditure: false },
    [LifestyleEventType.Expense]: { color: "text-green-600", icon: Activity, isExpenditure: true },
    [LifestyleEventType.Weight]: { color: "text-purple-600", icon: Scale, isExpenditure: false },
    [LifestyleEventType.Steps]: { color: "text-sky-600", icon: Footprints, isExpenditure: true },
    [LifestyleEventType.BasalMetabolism]: { color: "text-amber-700", icon: Flame, isExpenditure: true },
}

interface LifestyleEventSummaryProps {
    events: LifestyleEvent[] | null
    onLifestyleEventDeleted?: (id: string) => Promise<void>
}

export default function LifestyleEventSummary({ events, onLifestyleEventDeleted }: LifestyleEventSummaryProps) {
    const { t } = useTranslation()

    if (!events) {
        return (
            <div className="flex justify-center items-center rounded-xl shadow-md min-h-[400px]">
                <TailSpin
                    color="black"
                    height={80}
                    width={80} />
            </div>
        )
    }

    const groupedEvents = events.reduce((groups, event) => {
        const formattedDate = formatTimestamp(event.timestamp, t("general.format.date.year.included"))
        const dayEvents = groups.get(formattedDate)

        if (dayEvents) {
            dayEvents.push(event)
        }
        else {
            groups.set(formattedDate, [event])
        }
        return groups
    }, new Map<string, LifestyleEvent[]>())

    const sortedGroups = [...groupedEvents.entries()]
        .map(([formattedDate, dayEvents]) => [formattedDate, [...dayEvents].sort((a, b) => b.timestamp - a.timestamp)] as const)
        .sort(([firstDate], [secondDate]) => secondDate.localeCompare(firstDate))

    return (
        <div>
            <div className="bg-white rounded-xl shadow-md px-4 py-2">
                <div className="space-y-4">
                    {sortedGroups.map(([formattedDate, dayEvents]) => {
                        const basalMetabolismEvents = dayEvents.filter(event => event.type === LifestyleEventType.BasalMetabolism)
                        const totalBasalMetabolismCalories = basalMetabolismEvents.map(event => event.calories).reduce((sum, calories) => sum! + calories!, 0)

                        const stepEvents = dayEvents.filter(event => event.type === LifestyleEventType.Steps)
                        const totalSteps = stepEvents.reduce((sum, event) => sum + Number.parseFloat(event.content), 0)
                        const totalStepCalories = stepEvents.map(event => event.calories).reduce((sum, calories) => sum! + calories!, 0)

                        return (
                            <div key={formattedDate}>
                                <div className="text-xs font-semibold text-gray-400 uppercase tracking-wide my-2 px-1">
                                    {formattedDate}
                                </div>
                                <div className="divide-y divide-gray-200">
                                    {basalMetabolismEvents.length && (
                                        <EventRow
                                            isSummary={true}
                                            event={{
                                                id: `basalMetabolism-${formattedDate}`,
                                                type: LifestyleEventType.BasalMetabolism,
                                                timestamp: dayEvents[0]!.timestamp,
                                                content: t("lifestyle.label.basalMetabolism"),
                                                calories: totalBasalMetabolismCalories
                                            }}
                                            onLifestyleEventDeleted={onLifestyleEventDeleted} />
                                    )}
                                    {stepEvents.length && (
                                        <EventRow
                                            isSummary={true}
                                            event={{
                                                id: `steps-${formattedDate}`,
                                                type: LifestyleEventType.Steps,
                                                timestamp: dayEvents[0]!.timestamp,
                                                content: String(totalSteps),
                                                calories: totalStepCalories,
                                            }}
                                            onLifestyleEventDeleted={onLifestyleEventDeleted} />
                                    )}
                                    {dayEvents.filter(event => event.type !== LifestyleEventType.Steps && event.type !== LifestyleEventType.BasalMetabolism).map(event => (
                                        <EventRow
                                            isSummary={false}
                                            key={event.id}
                                            event={event}
                                            onLifestyleEventDeleted={onLifestyleEventDeleted} />
                                    ))}
                                </div>
                            </div>
                        )
                    })}
                </div>
            </div>
        </div>
    )
}

interface EventRowProps {
    event: LifestyleEvent
    isSummary: boolean
    onLifestyleEventDeleted?: (id: string) => Promise<void>
}

function EventRow({ event, isSummary, onLifestyleEventDeleted }: EventRowProps) {
    const { t } = useTranslation()
    const { formatCalories, formatKilograms, formatSteps } = useFormatters()
    const { showRemoveLifestyleEventToast } = usePredefinedUserInput()

    const { color, icon: Icon, isExpenditure } = EVENT_TYPES[event.type]


    const handleLifestyleEventDeleted = () => {
        if (onLifestyleEventDeleted) {
            showRemoveLifestyleEventToast(() => onLifestyleEventDeleted(event.id))
        }
    }

    return (
        <div className={`flex items-center justify-between gap-3 rounded-lg p-3 hover:bg-gray-100 transition-colors ${color}`}>
            <div className="flex items-center gap-4 min-w-0 flex-1">
                {!isSummary ? (
                    <span className="w-10 shrink-0 -ml-2 text-right text-xs opacity-60 whitespace-nowrap">
                        {formatTimestamp(event.timestamp, t("general.format.time"))}
                    </span>
                ) : (
                    <span className="w-10 shrink-0 -ml-2" aria-hidden="true" />
                )}
                <span className="shrink-0">
                    <Icon className={`w-5 h-5 ${color}`} />
                </span>
                <div className="min-w-0 flex-1 truncate">
                    {event.type === LifestyleEventType.Weight ? formatKilograms(Number.parseFloat(event.content))
                        : event.type === LifestyleEventType.Steps ? formatSteps(Number.parseFloat(event.content))
                            : event.content}
                </div>
            </div>
            <div className="flex items-center gap-3 shrink-0">
                <span className="text-right whitespace-nowrap">
                    {event.calories != null ? `${isExpenditure ? "-" : "+"}${formatCalories(Math.abs(event.calories))}` : null}
                </span>
                {onLifestyleEventDeleted && (
                    <span className="w-8 flex justify-center">
                        {!isSummary && (
                            <button
                                className="p-1 btn-icon"
                                onClick={handleLifestyleEventDeleted}>
                                <Trash2 size={16} />
                            </button>
                        )}
                    </span>
                )}
            </div>
        </div>
    )
}
