import { useEffect, useState } from "react"

import { useRegions } from "../hooks/useRegions"
import type { Category, GeographicalRegion } from "../types/CoreSwaggerTypes"
import Editor from "./Editor"
import RegionCardGrid from "./RegionCardGrid"
import RegionMap from "./RegionMap"

interface RegionEditorProps {
    categories: Category[] | null
    selectedKey?: string
    onKeySelected?: (key: string) => void
}

export default function RegionEditor({ categories, selectedKey, onKeySelected }: RegionEditorProps) {
    const keys = categories?.map(category => ({ name: category.name, label: category.name, target: category })) ?? null
    const selectedCategory = categories?.find(category => category.name === selectedKey)

    return (
        <Editor
            keys={keys}
            selectedKey={selectedKey}
            onKeySelected={onKeySelected}>
            {selectedCategory && (
                <EditedRegionContent
                    category={selectedCategory}
                    onCategorySelected={category => onKeySelected?.(category.name)} />
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

    const [activeRegion, setActiveRegion] = useState<GeographicalRegion | null>(null)

    useEffect(() => setActiveRegion(null), [category])

    return (
        <>
            <RegionCardGrid
                rowSize={3}
                regions={regions ?? null}
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
