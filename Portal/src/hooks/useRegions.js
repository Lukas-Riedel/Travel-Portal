import { useQuery } from "@tanstack/react-query"
import { useAuth } from "../contexts/AuthContext"
import { createCompositeRegion, createGeographicalRegion, listRegions } from "../clients/coreClient"

export const useRegions = ({ name } = {}) => {
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listRegions", name],
        queryFn: () => listRegions({ name }),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 24
    })

    const refetchRegions = _ => query.refetch()

    return {
        // TODO: Map to Region objects
        regions: query.data,
        createOrUpdateGeographicalRegion: (name, country, category, radius, geoJson) => createGeographicalRegion(name, country, category, radius, geoJson, true).then(refetchRegions),
        createOrUpdateCompositeRegion: (name, category, includedRegions, excludedRegions) => createCompositeRegion(name, category, includedRegions, excludedRegions, true).then(refetchRegions)
    }
}