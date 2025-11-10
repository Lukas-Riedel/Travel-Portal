import type { Category, CategoryCategory, CategoryMetadata } from "./CoreSwaggerTypes.ts"

export interface UseCategoryResult {
    category?: Category
    updateCategoryName: (name: string) => Promise<void>
    updateCategoryCategory: (category: CategoryCategory) => Promise<void>
    updateCategoryMetadata: (metadata: CategoryMetadata) => Promise<void>
    createCategoryHighlight: (photoId: string) => Promise<void>
    removeCategoryHighlight: (highlightId: string) => Promise<void>
    updateCategoryMainHighlight: (highlightId: string) => Promise<void>
    updateCategoryHighlightQualityAttributes: (highlightId: string, composition?: number, sky?: number, shadows?: number, circumstances?: number, atmosphere?: number) => Promise<void>
    removeCategory: () => Promise<void>
}