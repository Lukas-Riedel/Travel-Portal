import type { GeoJSON } from "geojson"

import type { CompositeRegion, GeographicalRegion } from "./CoreSwaggerTypes.ts"

export interface UseRegionsResult {
    regions: (GeographicalRegion | CompositeRegion)[] | null
    createGeographicalRegion: (name: string, country: string | undefined, category: string, radius: number, geoJson: GeoJSON) => Promise<GeographicalRegion>
    createCompositeRegion: (name: string, category: string, includedRegions: string[], excludedRegions?: string[]) => Promise<CompositeRegion>
    createOrUpdateGeographicalRegion: (name: string, country: string | undefined, category: string, radius: number, geoJson: GeoJSON) => Promise<GeographicalRegion>
    createOrUpdateCompositeRegion: (name: string, category: string, includedRegions: string[], excludedRegions?: string[]) => Promise<CompositeRegion>
}