import type { Category, CategoryCategory, CategoryMetadata, Highlight } from "./CoreSwaggerTypes.ts"

export interface UseCategoryResult {
    category?: Category
    updateCategoryName: (name: string) => Promise<Category>
    updateCategoryCategory: (category: CategoryCategory) => Promise<Category>
    updateCategoryMetadata: (metadata: CategoryMetadata) => Promise<Category>
    createCategoryHighlight: (photoId: string) => Promise<Highlight>
    removeCategoryHighlight: (highlightId: string) => Promise<void>
    updateCategoryMainHighlight: (highlightId: string) => Promise<Category>
    updateCategoryHighlightQualityAttributes: (highlightId: string, composition: number | null, sky: number | null, shadows: number | null, circumstances: number | null, atmosphere: number | null) => Promise<Highlight>
    removeCategory: () => Promise<void>
    refreshCategoryHighlights: (count: number) => Promise<Highlight[]>
}