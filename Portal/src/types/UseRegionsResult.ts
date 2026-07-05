import type { CompositeRegion, GeographicalRegion } from "./CoreSwaggerTypes.ts"
import type { GeoJSON } from "geojson"

export interface UseRegionsResult {
    regions?: (GeographicalRegion | CompositeRegion)[]
    createGeographicalRegion: (name: string, country: string, category: string, radius: number, geoJson: GeoJSON) => Promise<GeographicalRegion>
    createCompositeRegion: (name: string, category: string, includedRegions: string[], excludedRegions?: string[]) => Promise<CompositeRegion>
    createOrUpdateGeographicalRegion: (name: string, country: string, category: string, radius: number, geoJson: GeoJSON) => Promise<GeographicalRegion>
    createOrUpdateCompositeRegion: (name: string, category: string, includedRegions: string[], excludedRegions?: string[]) => Promise<CompositeRegion>
}