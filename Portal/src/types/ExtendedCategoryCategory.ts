import type { CategoryCategory } from "./CoreSwaggerTypes.ts"

export type ExtendedCategoryCategory = CategoryCategory | InternalCategoryCategory;

export enum InternalCategoryCategory {
    MostSpecificWithMetadata = "mostSpecificWithMetadata"
}