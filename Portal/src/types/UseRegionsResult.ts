import type { CompositeRegion, GeographicalRegion } from "./CoreSwaggerTypes.ts";

export interface UseRegionsResult {
    regions?: (GeographicalRegion | CompositeRegion)[]
    createGeographicalRegion: (name: string, country: string, category: string, radius: number, geoJson: any) => Promise<void>
    createCompositeRegion: (name: string, category: string, includedRegions: string[], excludedRegions?: string[]) => Promise<void>
    createOrUpdateGeographicalRegion: (name: string, country: string, category: string, radius: number, geoJson: any) => Promise<void>
    createOrUpdateCompositeRegion: (name: string, category: string, includedRegions: string[], excludedRegions?: string[]) => Promise<void>
}