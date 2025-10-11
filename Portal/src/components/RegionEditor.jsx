import { useEffect, useState } from "react"
import { TailSpin } from "react-loader-spinner"
import { useRegions } from "../hooks/useRegions"
import RegionCardGrid from "./RegionCardGrid"
import RegionMap from "./RegionMap"

export default function RegionEditor({ categories }) {
    const [selectedCategory, setSelectedCategory] = useState(null)

    return categories ? (
        <div className="flex h-[600px] border rounded-xl overflow-hidden">
            <div className="w-1/4 border-r p-3 overflow-y-auto bg-gray-100">
                {categories.map(category => (
                    <button
                        key={category.id}
                        onClick={() => setSelectedCategory(category)}
                        className={`block w-full text-left px-3 py-2 rounded hover:bg-gray-200 ${category.id === selectedCategory?.id ? "bg-gray-300 font-semibold" : ""}`}>
                        {category.name}
                    </button>
                ))}
            </div>
            <div className="flex-1 p-3 overflow-auto">
                {selectedCategory && (
                    <EditedRegionContent
                        category={selectedCategory}
                        onCategorySelected={category => categories.some(c => c.id === category.id) && setSelectedCategory(category)} />
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
    const { regions, createOrUpdateGeographicalRegion } = useRegions({ name: category.name })

    const [activeRegion, setActiveRegion] = useState(null)

    useEffect(() => setActiveRegion(null), [category])

    return (
        <>
            <RegionCardGrid
                regions={regions}
                onCategorySelected={onCategorySelected}
                onGeographicalRegionUpdated={createOrUpdateGeographicalRegion}
                onRegionVisualized={setActiveRegion} />
            {activeRegion && (
                <RegionMap region={activeRegion} />
            )}
        </>
    )
}
