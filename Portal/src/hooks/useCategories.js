import { useQuery } from "@tanstack/react-query"
import { useAuth } from "../contexts/AuthContext"
import { createCompositeRegion, createGeographicalRegion, listCategories } from "../clients/coreClient"

// TODO: This accepts string now, make it accept CategoryIncludedEntity[]
// TODO: This accepts string now, make it accept CategoryCategory[]
export const useCategories = ({ categories, include } = {}) => {
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listCategories", categories, include],
        queryFn: () => listCategories({ categories: categories?.split(","), include: include?.split(",") }),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 24
    })

    const refetchCategories = _ => query.refetch()

    return {
        // TODO: Map to Category objects
        categories: query.data,
        createGeographicalRegion: (name, country, category, radius, geoJson) => createGeographicalRegion(name, country, category, radius, geoJson).then(refetchCategories),
        createCompositeRegion: (name, category, includedRegions, excludedRegions) => createCompositeRegion(name, category, includedRegions, excludedRegions).then(refetchCategories)
    }
}