import type { Category, CategoryCategory, CategoryMetadata, Highlight } from "./CoreSwaggerTypes.ts"

export interface UseCategoryResult {
    category?: Category
    updateCategoryName: (name: string) => Promise<Category>
    updateCategoryCategory: (category: CategoryCategory) => Promise<Category>
    updateCategoryMetadata: (metadata: CategoryMetadata) => Promise<Category>
    createCategoryHighlight: (photoId: string) => Promise<Highlight>
    removeCategoryHighlight: (highlightId: string) => Promise<void>
    updateCategoryMainHighlight: (highlightId: string) => Promise<Category>
    updateCategoryHighlightQualityAttributes: (highlightId: string, composition?: number, sky?: number, shadows?: number, circumstances?: number, atmosphere?: number) => Promise<Highlight>
    removeCategory: () => Promise<void>
}