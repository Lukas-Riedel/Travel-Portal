import { useCallback, useEffect, useMemo, useState } from "react"
import { TailSpin } from "react-loader-spinner"
import { useRegions } from "../hooks/useRegions"
import RegionCardGrid from "./RegionCardGrid"
import RegionMap from "./RegionMap"
import { useSearchParams } from "react-router-dom"

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
                    <button
                        key={index}
                        onClick={() => setActiveKey(index)}
                        className={`block w-full text-left px-3 py-2 rounded hover:bg-gray-200 ${index === activeKeyIndex ? "bg-gray-300 font-semibold" : ""}`}>
                        {category.name}
                    </button>
                ))}
            </div>
            <div className="flex-1 p-3 overflow-auto">
                {selectedCategory && (
                    <EditedRegionContent
                        category={selectedCategory}
                        onCategorySelected={category => categories.some(c => c.id === category.id) && setActiveKey(index)} />
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
                regions={regions}
                onCategorySelected={onCategorySelected}
                onGeographicalRegionUpdated={createOrUpdateGeographicalRegion}
                onCompositeRegionUpdated={createOrUpdateCompositeRegion}
                onRegionVisualized={setActiveRegion} />
            {activeRegion && (
                <RegionMap region={activeRegion} />
            )}
        </>
    )
}
