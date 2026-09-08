import type { GeoJSON } from "geojson"

import type { CategoryIdentifier, CompositeRegion, GeographicalRegion } from "../types/CoreSwaggerTypes.ts"
import type { Region } from "../types/Region.ts"
import CardGrid from "./CardGrid.tsx"
import RegionCard from "./RegionCard.tsx"

interface RegionCardGridProps {
    regions: Region[] | null
    rowSize: number
    columnSize?: number
    onCategorySelected?: (category: CategoryIdentifier) => void
    onGeographicalRegionUpdated?: (nname: string, country: string | undefined, category: string, radius: number, geoJson: GeoJSON) => Promise<GeographicalRegion>
    onCompositeRegionUpdated?: (name: string, category: string, includedRegions: string[], excludedRegions?: string[]) => Promise<CompositeRegion>
    onRegionVisualized?: (region: GeographicalRegion) => void
}

export default function RegionCardGrid({ regions, rowSize, columnSize, onCategorySelected, onGeographicalRegionUpdated, onCompositeRegionUpdated, onRegionVisualized }: RegionCardGridProps) {
    return (
        <CardGrid
            rowSize={rowSize}
            columnSize={columnSize}>
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
