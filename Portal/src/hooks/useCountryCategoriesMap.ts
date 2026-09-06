import { useMemo } from "react"
import { CategoryCategory, type CategoryIncludedEntity } from "../types/CoreSwaggerTypes"
import type { UseCountryCategoriesMapResult } from "../types/UseCountryCategoriesMapResult"
import { useCategories } from "./useCategories"

interface UseCountryCategoriesMapProps {
    include?: CategoryIncludedEntity[]
}

export const useCountryCategoriesMap = ({ include }: UseCountryCategoriesMapProps = {}): UseCountryCategoriesMapResult => {
    const countryCategories = useCategories({ categories: [CategoryCategory.Country], include })

    return useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])
}