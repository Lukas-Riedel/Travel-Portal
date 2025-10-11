import CardGrid from "./CardGrid"
import RegionCard from "./RegionCard"

export default function RegionCardGrid({ regions, onCategorySelected, onRegionVisualized }) {
    return (
        <CardGrid cardsPerRowCount={3}>
            {regions?.map((region, index) => (
                <RegionCard
                    key={index}
                    region={region}
                    onCategorySelected={onCategorySelected}
                    onRegionVisualized={onRegionVisualized} />
            ))}
        </CardGrid>
    )
}
