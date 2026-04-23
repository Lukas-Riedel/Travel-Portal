import type { Region } from "../types/Region.ts"
import CardGrid from "./CardGrid.tsx"
import RegionCard from "./RegionCard.tsx"

interface RegionCardGridProps {
    regions: Region[] | null
    rowSize: number
    onCategorySelected?: (category: any) => void
    onGeographicalRegionUpdated?: (nname: string, country: string, category: string, radius: number, geoJson: any) => Promise<any>
    onCompositeRegionUpdated?: (name: string, category: string, includedRegions: string[], excludedRegions?: string[]) => Promise<any>
    onRegionVisualized?: (region: Region) => void
}

export default function RegionCardGrid({ regions, rowSize, onCategorySelected, onGeographicalRegionUpdated, onCompositeRegionUpdated, onRegionVisualized }: RegionCardGridProps) {
    return (
        <CardGrid rowSize={rowSize}>
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
