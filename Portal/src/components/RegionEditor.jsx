import { useCallback, useEffect, useMemo, useState } from "react"
import { TailSpin } from "react-loader-spinner"
import { useRegions } from "../hooks/useRegions"
import RegionCardGrid from "./RegionCardGrid"
import RegionMap from "./RegionMap"
import { useSearchParams } from "react-router-dom"
import { ExternalLink } from "lucide-react"
import AppLink from "./AppLink"

const keyUrlQueryParamName = "key"

// TODO: Merge with ConfigurationEditor and generalize.
export default function RegionEditor({ categories }) {
    const [searchParams, setSearchParams] = useSearchParams()
    const [selectedCategory, setSelectedCategory] = useState(null)

    const keyNames = useMemo(() => categories?.map(category => category.name), [categories])

    const activeKeyName = useMemo(() => {
        const keyNameFromUrl = searchParams.get(keyUrlQueryParamName)

        if (keyNameFromUrl && keyNames?.includes(keyNameFromUrl)) {
            return keyNameFromUrl
        }
        return keyNames?.[0]
    }, [keyNames, searchParams])

    const activeKeyIndex = useMemo(() => keyNames?.indexOf(activeKeyName), [keyNames, activeKeyName])

    const setActiveKey = useCallback(index => {
        if (index === -1) {
            return
        }

        const newSearchParams = new URLSearchParams(searchParams)
        const newKeyName = keyNames[index]

        if (index === 0) {
            newSearchParams.delete(keyUrlQueryParamName)
        }
        else {
            newSearchParams.set(keyUrlQueryParamName, newKeyName)
        }

        setSearchParams(newSearchParams)
    }, [categories, keyNames, searchParams, setSearchParams, setSelectedCategory])

    useEffect(() => {
        setSelectedCategory(categories?.[activeKeyIndex] ?? null)
    }, [categories, activeKeyIndex])

    return categories ? (
        <div className="flex h-[600px] border rounded-xl overflow-hidden">
            <div className="w-1/4 border-r p-3 overflow-y-auto bg-gray-100">
                {categories.map((category, index) => (
                    <div
                        key={index}
                        onClick={() => setActiveKey(index)}
                        className={`flex items-center justify-between w-full text-left px-3 py-2 rounded cursor-pointer transition-colors group ${index === activeKeyIndex ? "bg-gray-300 font-semibold text-gray-900" : "text-gray-700 hover:bg-gray-200"}`}>
                        <span className="truncate">
                            {category.name}
                        </span>
                        <AppLink
                            to={category}
                            onClick={e => e.stopPropagation()}
                            className={`ml-2 p-1 rounded transition-all ${index === activeKeyIndex ? "text-gray-600 hover:text-black hover:bg-gray-400/50" : "text-gray-400 hover:text-gray-700 hover:bg-gray-300/50 opacity-0 group-hover:opacity-100"}`}>
                            <ExternalLink className="w-4 h-4" />
                        </AppLink>
                    </div>
                ))}
            </div>
            <div className="flex-1 p-3 overflow-auto">
                {selectedCategory && (
                    <EditedRegionContent
                        category={selectedCategory}
                        onCategorySelected={category => setActiveKey(categories.findIndex(c => c.id === category.id))} />
                )}
                {!selectedCategory && (
                    <div className="flex items-center justify-center text-gray-500 h-full w-full">
                        Vyber region k editaci
                    </div>
                )}
            </div>
        </div>
    ) : (
        <div className="flex justify-center items-center min-h-[400px]">
            <TailSpin
                color="black"
                height={80}
                width={80} />
        </div>
    )
}

function EditedRegionContent({ category, onCategorySelected }) {
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
