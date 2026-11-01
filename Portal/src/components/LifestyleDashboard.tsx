import { endOfDay, startOfDay, subDays } from "date-fns"
import { useTranslation } from "react-i18next"
import { TailSpin } from "react-loader-spinner"
import { Bar, BarChart, CartesianGrid, Cell, Line, LineChart, ReferenceLine, ResponsiveContainer, Tooltip, XAxis, YAxis } from "recharts"

import { useFormatters } from "../hooks/useFormatters.ts"
import { type LifestyleEvent, LifestyleEventType } from "../types/CoreSwaggerTypes.ts"
import { formatTimestamp, getCurrentTimestamp, getDate, ONE_DAY_SECONDS } from "../utils/timeUtils.ts"

const CALORIES_PER_KILOGRAM = 7700
const DATE_KEY_FORMAT = "M/d"

interface LifestyleDashboardProps {
    events: LifestyleEvent[] | null
    monitoredPeriodDays: number
    recentPeriodDays: number
}

export default function LifestyleDashboard({ events, monitoredPeriodDays, recentPeriodDays }: LifestyleDashboardProps) {
    const { t } = useTranslation()
    const { formatCalories, formatDays, formatKilograms, formatSteps } = useFormatters()

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

    const dayKeys = Array.from({ length: recentPeriodDays }, (_, index) => formatTimestamp(subDays(new Date(), recentPeriodDays - 1 - index), DATE_KEY_FORMAT))

    const stepsByDay: Record<string, number> = {}
    dayKeys.forEach(dayKey => { stepsByDay[dayKey] = 0 })

    events
        .filter(event => event.type === LifestyleEventType.Steps)
        .forEach(event => {
            const dayKey = formatTimestamp(event.timestamp, DATE_KEY_FORMAT)
            if (dayKey in stepsByDay) {
                stepsByDay[dayKey] = (stepsByDay[dayKey] ?? 0) + Number(event.content)
            }
        })

    const stepsPerDay = dayKeys.map((dateKey, index) => ({
        day: formatTimestamp(startOfDay(subDays(new Date(), recentPeriodDays - 1 - index)), t("general.format.date.year.included")),
        dateKey,
        steps: stepsByDay[dateKey] ?? 0,
    }))

    const latestWeight = (() => {
        const weights = [...events]
            .filter(event => event.type === LifestyleEventType.Weight)
            .sort((a, b) => b.timestamp - a.timestamp)
        return weights[0] ?? null
    })()

    const cutoff = getCurrentTimestamp() - monitoredPeriodDays * ONE_DAY_SECONDS
    const weightTrend = [...events]
        .filter(event => event.type === LifestyleEventType.Weight && event.timestamp >= cutoff)
        .sort((a, b) => a.timestamp - b.timestamp)
        .map(event => ({ timestamp: event.timestamp, weight: Number.parseFloat(event.content!) }))
        .filter(event => Number.isFinite(event.weight))

    const deficitChart = dayKeys.map((dateKey, index) => {
        const dayOffset = recentPeriodDays - 1 - index
        const day = subDays(new Date(), dayOffset)
        const start = startOfDay(day)
        const end = endOfDay(day)
        const startTimestamp = Math.floor(start.getTime() / 1000)
        const endTimestamp = Math.floor(end.getTime() / 1000)
        let intake = 0
        let expenditure = 0

        events
            .filter(event => event.timestamp >= startTimestamp && event.timestamp <= endTimestamp)
            .forEach(event => {
                if (!event.calories) {
                    return
                }

                if (event.type === LifestyleEventType.Intake) {
                    intake += event.calories
                }
                else if ([LifestyleEventType.Steps, LifestyleEventType.BasalMetabolism, LifestyleEventType.Expense].includes(event.type)) {
                    expenditure += Math.abs(event.calories)
                }
            })

        return {
            day: formatTimestamp(start, t("general.format.date.year.included")),
            dateKey,
            intake: Math.round(intake),
            expenditure: Math.round(expenditure),
            deficit: Math.round(expenditure - intake),
        }
    }).filter(day => day.intake > 0)

    const currentWeight = latestWeight ? Number.parseFloat(latestWeight.content!) : Number.NaN
    const weightPrediction = (() => {
        if (!Number.isFinite(currentWeight) || deficitChart.length === 0) {
            return null
        }

        const totalDeficit = deficitChart.reduce((sum, day) => sum + day.deficit, 0)
        const averageDailyDeficit = totalDeficit / deficitChart.length
        const projectedChange = -(averageDailyDeficit * monitoredPeriodDays) / CALORIES_PER_KILOGRAM

        return { projectedChange }
    })()

    const points = new Map<number, { timestamp: number, actualWeight?: number, predictedWeight?: number }>()

    weightTrend.forEach(entry => {
        points.set(entry.timestamp, { timestamp: entry.timestamp, actualWeight: entry.weight })
    })

    if (Number.isFinite(currentWeight) && latestWeight) {
        const latestPoint = points.get(latestWeight.timestamp)
        points.set(latestWeight.timestamp, {
            ...latestPoint,
            timestamp: latestWeight.timestamp,
            actualWeight: currentWeight,
        })
    }

    if (weightPrediction && latestWeight) {
        const currentTimestamp = latestWeight.timestamp
        for (let i = 0; i <= monitoredPeriodDays; i++) {
            const date = getDate(currentTimestamp)
            date.setDate(date.getDate() + i)
            const timestamp = Math.floor(date.getTime() / 1000)
            const existingPoint = points.get(timestamp)
            points.set(timestamp, {
                ...existingPoint,
                timestamp,
                predictedWeight: currentWeight + (weightPrediction.projectedChange * i) / monitoredPeriodDays,
            })
        }
    }

    const weightForecastChart = [...points.values()].sort((a, b) => a.timestamp - b.timestamp)

    const values = weightForecastChart
        .flatMap(point => [point.actualWeight, point.predictedWeight])
        .filter((value): value is number => value != null && Number.isFinite(value))

    const weightChartDomain: [number, number] = values.length === 0
        ? [0, 1]
        : (() => {
            const minimum = Math.min(...values)
            const maximum = Math.max(...values)
            const padding = Math.max(1, (maximum - minimum) * 0.15)

            return [Math.floor(minimum - padding), Math.ceil(maximum + padding)]
        })()

    return (
        <div className="space-y-4 md:space-y-8">
            <div className="bg-white rounded-xl shadow-md p-5">
                <div className="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <div className="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">
                            {t("lifestyle.section.weightPrediction", { period: formatDays(monitoredPeriodDays) })}
                        </div>
                    </div>
                    {weightForecastChart.length > 0 && (
                        <div className="w-full mt-4">
                            <ResponsiveContainer width="100%" height={240}>
                                <LineChart data={weightForecastChart} margin={{ top: 8, right: 12, left: 0, bottom: 0 }}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                                    <XAxis
                                        dataKey="timestamp"
                                        type="number"
                                        domain={["dataMin", "dataMax"]}
                                        tickFormatter={(value: number) => formatTimestamp(value, t("general.format.date.year.included"))}
                                        tick={{ fontSize: 10 }} />
                                    <YAxis
                                        tick={{ fontSize: 10 }}
                                        width={48}
                                        domain={weightChartDomain}
                                        tickFormatter={formatKilograms} />
                                    <Tooltip
                                        content={({ active, payload, label }) => {
                                            if (!active || !payload?.length) {
                                                return null
                                            }

                                            return (
                                                <div className="bg-white border border-gray-200 rounded-lg shadow p-2 text-xs space-y-0.5">
                                                    <div className="font-semibold text-gray-600 mb-1">
                                                        {formatTimestamp(Number(label), t("general.format.date.year.included"))}
                                                    </div>
                                                    {payload.map((entry, index) => {
                                                        const isActual = entry.dataKey === "actualWeight"
                                                        return (
                                                            <div
                                                                key={`${String(entry.dataKey)}-${index}`}
                                                                className={isActual ? "text-purple-500" : "text-amber-500"}>
                                                                {isActual ? t("lifestyle.label.measuredWeight") : t("lifestyle.label.predictedWeight")}: {formatKilograms(Number(entry.value ?? 0))}
                                                            </div>
                                                        )
                                                    })}
                                                </div>
                                            )
                                        }} />
                                    <Line
                                        type="monotone"
                                        dataKey="actualWeight"
                                        name="actualWeight"
                                        stroke="#8b5cf6"
                                        strokeWidth={2.5}
                                        dot={{ r: 3, strokeWidth: 1 }}
                                        connectNulls={false} />
                                    {weightPrediction && (
                                        <Line
                                            type="monotone"
                                            dataKey="predictedWeight"
                                            name="predictedWeight"
                                            stroke="#f59e0b"
                                            strokeWidth={2}
                                            strokeDasharray="6 4"
                                            dot={{ r: 3, strokeWidth: 1 }}
                                            connectNulls={false} />
                                    )}
                                </LineChart>
                            </ResponsiveContainer>
                            <div className="flex flex-wrap gap-4 text-xs text-gray-500 mt-1">
                                <span className="flex items-center gap-1.5">
                                    <span className="inline-block w-5 border-t-2 border-purple-500" />
                                    {t("lifestyle.label.measuredWeight")}
                                </span>
                                {weightPrediction && (
                                    <span className="flex items-center gap-1.5">
                                        <span className="inline-block w-5 border-t-2 border-dashed border-amber-500" />
                                        {t("lifestyle.label.predictedWeight")}
                                    </span>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="bg-white rounded-xl shadow-md p-4">
                    <div className="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">
                        {t("lifestyle.section.stepsLastDays", { period: formatDays(recentPeriodDays) })}
                    </div>
                    <ResponsiveContainer width="100%" height={200}>
                        <BarChart data={stepsPerDay} barSize={22}>
                            <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                            <XAxis dataKey="day" tick={{ fontSize: 10 }} />
                            <YAxis tick={{ fontSize: 10 }} width={36} />
                            <Tooltip
                                content={({ active, payload, label }) => {
                                    if (!active || !payload?.length) return null
                                    const d = payload[0]?.payload
                                    return (
                                        <div className="bg-white border border-gray-200 rounded-lg shadow p-2 text-xs space-y-0.5">
                                            <div className="font-semibold text-gray-600 mb-1">{label}</div>
                                            <div className="text-blue-400">{t("lifestyle.label.stepsCount")}: {formatSteps(d.steps)}</div>
                                        </div>
                                    )
                                }} />
                            <Bar dataKey="steps" radius={[3, 3, 0, 0]}>
                                {stepsPerDay.map((entry, i) => (
                                    <Cell
                                        key={i}
                                        fill={entry.dateKey === formatTimestamp(getCurrentTimestamp(), DATE_KEY_FORMAT) ? "#3b82f6" : "#cbd5e1"} />
                                ))}
                            </Bar>
                        </BarChart>
                    </ResponsiveContainer>
                </div>

                <div className="bg-white rounded-xl shadow-md p-4">
                    <div className="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">
                        {t("lifestyle.section.deficitLastDays", { period: formatDays(recentPeriodDays) })}
                    </div>
                    <ResponsiveContainer width="100%" height={200}>
                        <BarChart data={deficitChart} barSize={28}>
                            <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                            <XAxis dataKey="day" tick={{ fontSize: 10 }} />
                            <YAxis tick={{ fontSize: 10 }} width={44} />
                            <Tooltip
                                formatter={(v: number | undefined, name: string | undefined) => [formatCalories(v ?? 0), name ? t(`lifestyle.label.${name}`) : ""]}
                                content={({ active, payload, label }) => {
                                    if (!active || !payload?.length) return null
                                    const d = payload[0]?.payload
                                    return (
                                        <div className="bg-white border border-gray-200 rounded-lg shadow p-2 text-xs space-y-0.5">
                                            <div className="font-semibold text-gray-600 mb-1">{label}</div>
                                            <div className="text-orange-400">{t("lifestyle.label.intake")}: {formatCalories(d.intake)}</div>
                                            <div className="text-blue-400">{t("lifestyle.label.expenditure")}: {formatCalories(d.expenditure)}</div>
                                            <div className={`font-semibold mt-1 ${d.deficit >= 0 ? "text-green-600" : "text-red-500"}`}>
                                                {d.deficit >= 0
                                                    ? `${t("lifestyle.label.deficit")}: ${formatCalories(d.deficit)}`
                                                    : `${t("lifestyle.label.surplus")}: ${formatCalories(Math.abs(d.deficit))}`}
                                            </div>
                                        </div>
                                    )
                                }} />
                            <ReferenceLine y={0} stroke="#94a3b8" />
                            <Bar dataKey="deficit" radius={[3, 3, 0, 0]}>
                                {deficitChart.map((entry, i) => (
                                    <Cell key={i} fill={entry.deficit >= 0 ? "#4ade80" : "#f87171"} />
                                ))}
                            </Bar>
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            </div>
        </div>
    )
}
