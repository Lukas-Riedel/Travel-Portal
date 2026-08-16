import { useCallback, useEffect, useMemo, useState } from "react"
import { TailSpin } from "react-loader-spinner"
import { useRegions } from "../hooks/useRegions"
import RegionCardGrid from "./RegionCardGrid"
import RegionMap from "./RegionMap"
import { useSearchParams } from "react-router-dom"
import { ExternalLink } from "lucide-react"
import AppLink from "./AppLink"
import type { Category } from "../types/CoreSwaggerTypes"
import Editor from "./Editor"

interface RegionEditorProps {
    categories: Category[] | null
}

export default function RegionEditor({ categories }: RegionEditorProps) {
    const [selectedCategory, setSelectedCategory] = useState(null)

    const keys = useMemo(() => categories?.map(category => ({ name: category.name, label: category.name, target: category })), [categories])

    return (
        <Editor
            keys={keys}
            onKeySelected={name => setSelectedCategory(categories.find(category => category.name === name))}>
            {selectedCategory && (
                <EditedRegionContent
                    category={selectedCategory}
                    // TODO: This doesn't set the category name in the URL because this component doesn't have access to search params. Resolve by resolving TODO in Editor first.
                    onCategorySelected={setSelectedCategory} />
            )}
        </Editor>
    )
}

interface EditedRegionContentProps {
    category: Category
    onCategorySelected: (category: Category) => void
}

function EditedRegionContent({ category, onCategorySelected }: EditedRegionContentProps) {
    const { regions, createOrUpdateGeographicalRegion, createOrUpdateCompositeRegion } = useRegions({ name: category.name })

    const [activeRegion, setActiveRegion] = useState(null)

    useEffect(() => setActiveRegion(null), [category])

    return (
        <>
            <RegionCardGrid
                rowSize={3}
                regions={regions}
                onCategorySelected={onCategorySelected}
                onGeographicalRegionUpdated={createOrUpdateGeographicalRegion}
                onCompositeRegionUpdated={createOrUpdateCompositeRegion}
                onRegionVisualized={setActiveRegion} />
            {activeRegion && (
                <RegionMap regions={[activeRegion]} />
            )}
        </>
    )
}
