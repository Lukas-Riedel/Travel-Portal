import CardGrid from "./CardGrid"
import StatisticsCard from "./StatisticsCard"

export default function StatisticsCardGrid({ statistics }) {
    return (
        <CardGrid cardsPerRowCount={2}>
            {statistics?.map(statisticsRecord => (
                <StatisticsCard
                    key={statisticsRecord.key}
                    statistics={statisticsRecord} />
            ))}
        </CardGrid>
    )
}
