import CardGrid from "./CardGrid.tsx"
import RegionCard from "./RegionCard"

export default function RegionCardGrid({ regions, onCategorySelected, onGeographicalRegionUpdated, onCompositeRegionUpdated, onRegionVisualized }) {
    return (
        <CardGrid rowSize={3}>
            {regions?.map((region, index) => (
                <RegionCard
                    key={index}
                    region={region}
                    onCategorySelected={onCategorySelected}
                    onGeographicalRegionUpdated={onGeographicalRegionUpdated}
                    onCompositeRegionUpdated={onCompositeRegionUpdated}
                    onRegionVisualized={onRegionVisualized} />
            ))}
        </CardGrid>
    )
}
