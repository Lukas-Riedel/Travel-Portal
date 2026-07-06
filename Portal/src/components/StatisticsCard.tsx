import { useMemo } from "react"
import { ResponsiveContainer, BarChart, Bar, XAxis, YAxis, Tooltip, PieChart, Pie, Cell } from "recharts"
import { useTranslation } from "react-i18next"
import LoadingCard from "./LoadingCard.tsx"
import { useConfiguration } from "../contexts/ConfigContext.tsx"
import { getCurrentYear } from "../utils/timeUtils.ts"
import { useFormatters } from "../hooks/useFormatters.ts"
import { StatisticsName, StatisticsUnit, type Statistics, type Year } from "../types/CoreSwaggerTypes.ts"
import Card from "./Card.tsx"

interface StatisticsCardProps {
    statistics: Statistics | null
    years?: Year[]
}

interface ChartValue {
    name: string
    value: number
}

interface ChartProps {
    values: ChartValue[]
    unit: StatisticsUnit
}

interface TooltipProps {
    active?: boolean
    payload?: Array<{ payload: ChartValue }>
    unit: StatisticsUnit
}

const CHART_TYPES: Partial<Record<StatisticsName, React.ComponentType<ChartProps>>> = {
    [StatisticsName.VISITED_PLACES_PER_CATEGORY]: StandingStatisticsPieChart,
    [StatisticsName.VISITED_PLACES_PER_COUNTRY]: StandingStatisticsPieChart,
    [StatisticsName.VISITED_PLACES_PER_CONTINENT]: StandingStatisticsPieChart,
    [StatisticsName.TOTAL_TRAVEL_DAYS_PER_CONTINENT]: StandingStatisticsPieChart,
    [StatisticsName.TOTAL_TRAVEL_DAYS_PER_COUNTRY]: StandingStatisticsPieChart,
    [StatisticsName.MOST_PHOTOS_PER_CATEGORY]: StandingStatisticsPieChart,
    [StatisticsName.MOST_PHOTOS_PER_COUNTRY]: StandingStatisticsPieChart,
    [StatisticsName.MOST_USED_AIRPORTS]: StandingStatisticsPieChart,
    [StatisticsName.MOST_USED_AIRCRAFTS]: StandingStatisticsPieChart,
    [StatisticsName.MOST_USED_AIRLINES]: StandingStatisticsPieChart,
    [StatisticsName.MOST_USED_CAMERAS]: StandingStatisticsPieChart
}

export default function StatisticsCard({ statistics, years }: StatisticsCardProps) {
    const { t } = useTranslation()
    const { configuration } = useConfiguration()
    const { formatStatisticsUnit } = useFormatters()

    const StandingStatisticsChart = useMemo(() => statistics ? (CHART_TYPES[statistics.name] || StandingStatisticsBarChart) : StandingStatisticsBarChart, [statistics?.name])

    if (!statistics) {
        return (
            <LoadingCard />
        )
    }

    return (
        <Card>
            <div className="text-lg text-center font-semibold mb-4">
                {t(`statistics.name.${statistics.name}`)}
            </div>
            <div className="flex-grow flex items-center justify-center">
                <div className="w-full">
                    {Array.isArray(statistics.value) ? (
                        <>
                            <ol className="list-decimal list-inside space-y-1 text-xs text-gray-700 text-center">
                                {statistics.value.map((item, index) => (
                                    <li key={index}>
                                        <span>
                                            {item.key}
                                        </span>
                                        {" "}
                                        <span className="text-gray-400">
                                            ({formatStatisticsUnit(statistics.unit, Number(item.value), configuration?.expensify?.mainCurrency)})
                                        </span>
                                    </li>
                                ))}
                            </ol>
                            <div>
                                <StandingStatisticsChart
                                    values={statistics.value.map(value => ({ name: value.key, value: Number(value.value) }))}
                                    unit={statistics.unit} />
                            </div>
                        </>
                    ) : (
                        <>
                            <div className="text-center text-lg">
                                {formatStatisticsUnit(statistics.unit, Number(statistics.value), configuration?.expensify?.mainCurrency ?? "")}
                            </div>
                            {years && (
                                <div>
                                    <StandingStatisticsChart
                                        values={years.filter(year => year.id <= getCurrentYear()).map(year => ({ name: `${year.id}`, value: Number(year.statistics?.find(item => item.name === statistics.name)?.value) })).filter(year => year.value)}
                                        unit={statistics.unit} />
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </Card>
    )
}

function StatisticsContainer({ children }: { children: React.ReactNode }) {
    return (
        <div className="w-full h-[300px] p-4">
            <ResponsiveContainer
                width="100%"
                height="100%">
                {children}
            </ResponsiveContainer>
        </div>
    )
}

function StandingStatisticsBarChart({ values, unit }: ChartProps) {
    const { configuration } = useConfiguration()
    const { formatStatisticsUnit } = useFormatters()

    return (
        <StatisticsContainer>
            <BarChart data={values}>
                <XAxis dataKey="name" />
                <YAxis
                    tickFormatter={value => formatStatisticsUnit(unit, value, configuration?.expensify?.mainCurrency)}
                    padding={{ top: 30, bottom: 0 }} />
                <Tooltip content={<CustomTooltip unit={unit} />} />
                <Bar
                    dataKey="value"
                    fill="#33ccff"
                    radius={[6, 6, 0, 0]} />
            </BarChart>
        </StatisticsContainer>
    )
}

function StandingStatisticsPieChart({ values, unit }: ChartProps) {
    return (
        <StatisticsContainer>
            <PieChart>
                <Pie
                    data={values}
                    dataKey="value"
                    nameKey="name"
                    cx="50%"
                    cy="50%"
                    outerRadius={100}
                    label={({ name }) => name}>
                    {values.map((_, index) => (
                        <Cell
                            key={index}
                            fill={`hsl(${(index * 360) / values.length}, 65%, 55%)`} />
                    ))}
                </Pie>
                <Tooltip content={<CustomTooltip unit={unit} />} />
            </PieChart>
        </StatisticsContainer>
    )
}

function CustomTooltip({ active, payload, unit }: TooltipProps) {
    const { configuration } = useConfiguration()
    const { formatStatisticsUnit } = useFormatters()

    return active && payload && (
        <div className="bg-white/90 bg-white border shadow px-3 py-2 rounded">
            <div className="font-semibold">
                {payload[0].payload.name}
            </div>
            <div>
                {formatStatisticsUnit(unit, payload[0].payload.value, configuration?.expensify?.mainCurrency)}
            </div>
        </div>
    )
}