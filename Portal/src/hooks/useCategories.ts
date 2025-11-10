import { listCategories } from "../clients/coreClient.ts"
import type { CategoryCategory, CategoryIncludedEntity } from "../types/CoreSwaggerTypes.ts"
import type { UseCategoriesResult } from "../types/UseCategoriesResult.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

export const useCategories = ({ categories, include }: { categories?: CategoryCategory[], include?: CategoryIncludedEntity[] } = {}): UseCategoriesResult => {
    const { response } = useQuery({
        queryKey: ["listCategories", ...(categories ?? []), ...(include ?? [])],
        queryFn: () => listCategories({ categories, include }),
        staleTime: ONE_DAY_SECONDS * 1000
    })

    return response
}