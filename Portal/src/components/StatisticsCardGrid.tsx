import type { Statistics, Year } from "../types/CoreSwaggerTypes.ts"
import CardGrid from "./CardGrid.tsx"
import StatisticsCard from "./StatisticsCard.tsx"

interface StatisticsCardGridProps {
    statistics: Statistics[] | null
    rowSize: number
    years?: Year[]
}

export default function StatisticsCardGrid({ statistics, rowSize, years }: StatisticsCardGridProps) {
    return (
        <CardGrid rowSize={rowSize}>
            {statistics?.map(item => (
                <StatisticsCard
                    key={item.name}
                    statistics={item}
                    years={years} />
            ))}
        </CardGrid>
    )
}