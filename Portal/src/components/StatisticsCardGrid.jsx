import CardGrid from "./CardGrid.tsx"
import StatisticsCard from "./StatisticsCard"

export default function StatisticsCardGrid({ statistics, years }) {
    return (
        <CardGrid rowSize={2}>
            {statistics?.map(statisticsRecord => (
                <StatisticsCard
                    key={statisticsRecord.key}
                    statistics={statisticsRecord}
                    years={years} />
            ))}
        </CardGrid>
    )
}