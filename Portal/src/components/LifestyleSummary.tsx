import { Activity, Flame, Scale, TrendingDown, TrendingUp, Utensils } from "lucide-react"
import { useTranslation } from "react-i18next"

import { useFormatters } from "../hooks/useFormatters.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { type LifestyleEvent, LifestyleEventType } from "../types/CoreSwaggerTypes.ts"
import { FactCardAccent } from "../types/FactCardAccent.ts"
import { formatTimestamp, getCurrentTimestamp, isToday, ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import CardGrid from "./CardGrid.tsx"
import FactCard from "./FactCard.tsx"

export interface LifestyleSummaryProps {
    events: LifestyleEvent[] | null
    monitoredPeriodDays: number
    onLifestyleEventCreated?: (type: LifestyleEventType, content: string, timestamp: number, calories: number) => Promise<LifestyleEvent>
}

export default function LifestyleSummary({ events, monitoredPeriodDays, onLifestyleEventCreated }: LifestyleSummaryProps) {
    const { t } = useTranslation()
    const { formatCalories, formatDays, formatKilograms } = useFormatters()
    const { showAddLifestyleIntakeToast, showAddLifestyleActivityToast } = usePredefinedUserInput()

    const latestWeightEvent = events
        ? [...events]
            .filter(event => event.type === LifestyleEventType.Weight)
            .sort((first, second) => second.timestamp - first.timestamp)[0]
        : null

    const weightTrend = events
        ? [...events]
            .filter(event => event.type === LifestyleEventType.Weight && event.timestamp >= getCurrentTimestamp() - monitoredPeriodDays * ONE_DAY_SECONDS)
            .sort((first, second) => first.timestamp - second.timestamp)
            .map(event => ({ timestamp: event.timestamp, weight: Number.parseFloat(event.content!) }))
            .filter(event => Number.isFinite(event.weight))
        : []

    const weightDelta = weightTrend.length >= 2
        ? (weightTrend[weightTrend.length - 1]?.weight ?? 0) - (weightTrend[0]?.weight ?? 0)
        : undefined

    let todayIntake = 0
    let todayExpenditure = 0
    let latestIntakeTimestamp: number | undefined
    let latestExpenditureTimestamp: number | undefined

    if (events) {
        events
            .filter(event => isToday(event.timestamp))
            .forEach(event => {
                if (event.calories == null || !Number.isFinite(event.calories)) {
                    return
                }

                if (event.type === LifestyleEventType.Intake) {
                    todayIntake += event.calories
                    latestIntakeTimestamp = Math.max(latestIntakeTimestamp ?? event.timestamp, event.timestamp)
                }
                else if ([LifestyleEventType.Steps, LifestyleEventType.BasalMetabolism, LifestyleEventType.Expense].includes(event.type)) {
                    todayExpenditure += Math.abs(event.calories)
                    latestExpenditureTimestamp = Math.max(latestExpenditureTimestamp ?? event.timestamp, event.timestamp)
                }
            })
    }

    const handleLifestyleIntakeEventCreated = () => {
        if (onLifestyleEventCreated) {
            showAddLifestyleIntakeToast((content: string, timestamp: Date, calories: number) => onLifestyleEventCreated(LifestyleEventType.Intake, content, timestamp.getTime() / 1000, calories))
        }
    }

    const handleLifestyleActivityEventCreated = () => {
        if (onLifestyleEventCreated) {
            showAddLifestyleActivityToast((content: string, timestamp: Date, calories: number) => onLifestyleEventCreated(LifestyleEventType.Expense, content, timestamp.getTime() / 1000, calories))
        }
    }

    return (
        <div className="space-y-4 md:space-y-8">
            <div className="flex items-center justify-between flex-wrap gap-3 px-4 md:px-0">
                <h1 className="text-2xl font-bold text-gray-800">
                    {t("lifestyle.title")}
                </h1>
                {onLifestyleEventCreated && (
                    <div className="flex gap-2">
                        <button
                            className="btn-pill flex items-center gap-1.5 text-sm"
                            onClick={handleLifestyleIntakeEventCreated}>
                            <Utensils size={14} />
                            {t("lifestyle.prompt.create.intake.message")}
                        </button>
                        <button
                            className="btn-pill flex items-center gap-1.5 text-sm"
                            onClick={handleLifestyleActivityEventCreated}>
                            <Activity size={14} />
                            {t("lifestyle.prompt.create.activity.message")}
                        </button>
                    </div>
                )}
            </div>
            <CardGrid
                rowSize={4}>
                <FactCard
                    icon={Scale}
                    label={t("lifestyle.fact.currentWeight")}
                    value={events === null ? null : latestWeightEvent ? formatKilograms(Number.parseFloat(latestWeightEvent.content!)) : "-"}
                    description={latestWeightEvent ? formatTimestamp(latestWeightEvent.timestamp, t("general.format.datetime.year.excluded")) : undefined} />
                <FactCard
                    icon={weightDelta != null && weightDelta <= 0 ? TrendingDown : TrendingUp}
                    label={`${t("lifestyle.fact.weightTrend")} (${formatDays(monitoredPeriodDays)})`}
                    value={events === null ? null : weightDelta !== undefined ? formatKilograms(weightDelta) : "-"}
                    accent={weightDelta !== undefined ? (weightDelta < 0 ? FactCardAccent.Positive : weightDelta > 0 ? FactCardAccent.Negative : FactCardAccent.Neutral) : FactCardAccent.Neutral} />
                <FactCard
                    icon={Utensils}
                    label={t("lifestyle.fact.todayIntake")}
                    value={events === null ? null : (todayIntake > 0 ? formatCalories(todayIntake) : "-")}
                    description={latestIntakeTimestamp !== undefined ? formatTimestamp(latestIntakeTimestamp, t("general.format.datetime.year.excluded")) : undefined} />
                <FactCard
                    icon={Flame}
                    label={t("lifestyle.fact.todayExpenditure")}
                    value={events === null ? null : (todayExpenditure > 0 ? formatCalories(todayExpenditure) : "-")}
                    description={latestExpenditureTimestamp !== undefined ? formatTimestamp(latestExpenditureTimestamp, t("general.format.datetime.year.excluded")) : undefined} />
            </CardGrid>
        </div>
    )
}
