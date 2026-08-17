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
    selectedKey?: string
    onKeySelected?: (key: string) => void
}

export default function RegionEditor({ categories, selectedKey, onKeySelected }: RegionEditorProps) {
    const keys = useMemo(() => categories?.map(category => ({ name: category.name, label: category.name, target: category })), [categories])
    const selectedCategory = useMemo(() => categories?.find(category => category.name === selectedKey), [categories, selectedKey])

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
