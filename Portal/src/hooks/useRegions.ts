import { createCompositeRegion, createGeographicalRegion, listRegions } from "../clients/coreClient.ts"
import type { UseRegionsResult } from "../types/UseRegionsResult.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

interface UseRegionsProps {
    name?: string
    enabled?: boolean
}

export const useRegions = ({ name, enabled }: UseRegionsProps = {}): UseRegionsResult => {
    const { response, refetchResponse } = useQuery({
        queryKey: ["listRegions", name],
        queryFn: () => listRegions({ name }),
        staleTime: ONE_DAY_SECONDS * 1000,
        enabled
    })

    return {
        regions: response,
        createGeographicalRegion: (name: string, country: string, category: string, radius: number, geoJson: any) => createGeographicalRegion(name, country, category, radius, geoJson, false).then(refetchResponse),
        createCompositeRegion: (name: string, category: string, includedRegions: string[], excludedRegions?: string[]) => createCompositeRegion(name, category, includedRegions, excludedRegions, false).then(refetchResponse),
        createOrUpdateGeographicalRegion: (name: string, country: string, category: string, radius: number, geoJson: any) => createGeographicalRegion(name, country, category, radius, geoJson, true).then(refetchResponse),
        createOrUpdateCompositeRegion: (name: string, category: string, includedRegions: string[], excludedRegions?: string[]) => createCompositeRegion(name, category, includedRegions, excludedRegions, true).then(refetchResponse)
    }
}