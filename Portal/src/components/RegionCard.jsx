import LoadingCard from "./LoadingCard.tsx"
import { formatKilometers } from "../utils/formatters"
import { Copy, Map, Wrench } from "lucide-react"
import { useUserInput } from "../hooks/useUserInput.tsx"
import { getGeoJson, getGeoFeatures } from "../utils/helpers.js"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"

export default function RegionCard({ region, onCategorySelected, onGeographicalRegionUpdated, onCompositeRegionUpdated, onRegionVisualized }) {
    const { showCopyRegionGeoJsonToast, showOverwriteGeographicalRegionToast, showOverwriteCompositeRegionToast } = usePredefinedUserInput()

    const regionProperties = region && {
        "Typ": region.geoJson ? "Geografický" : "Kompozitní",
        "Rádius": region.radius > 0 && formatKilometers(region.radius),
        "Stát": region.countryCategory && region.countryCategory.name,
        "Podtyp": region.geoJson?.geometry?.type && (region.geoJson?.geometry?.type === "Point" ? "Bod" : "Oblast"),
        "Souřadnice": region.geoJson?.geometry?.type === "Point" && region.geoJson?.geometry?.coordinates?.length === 2 ? `${region.geoJson.geometry.coordinates[1].toFixed(4)}, ${region.geoJson.geometry.coordinates[0].toFixed(4)}` : undefined,
        "Zahrnuté regiony": region.includedCategories && (
            <ul className="space-y-0.5 list-inside list-disc">
                {region.includedCategories.map(category => (
                    <li
                        key={category.id}
                        className="ml-2 text-gray-700 hover:text-gray-500 hover:underline hover:cursor-pointer"
                        onClick={() => onCategorySelected(category)}>
                        {category.name}
                    </li>
                ))}
            </ul>
        ),
        "Vyloučené regiony": region.excludedCategories && (
            <ul className="space-y-0.5 list-inside list-disc">
                {region.excludedCategories.map(category => (
                    <li
                        key={category.id}
                        className="ml-2 text-gray-700 hover:text-gray-500 hover:underline hover:cursor-pointer"
                        onClick={() => onCategorySelected(category)}>
                        {category.name}
                    </li>
                ))}
            </ul>
        )
    }

    const handleCopyGeoJsonToClipboard = () => {
        showCopyRegionGeoJsonToast(() => navigator.clipboard.writeText(JSON.stringify(region.geoJson)))
    }

    const handleOverwriteGeographicalRegion = () => {
        showOverwriteGeographicalRegionToast(region, (radius, geoJson) => {
            const geoFeatures = getGeoFeatures(geoJson)
            if (geoFeatures.length !== 1) {
                return Promise.reject("There must be exactly one feature in the GeoJSON, but there are " + geoFeatures.length + " features.")
            }

            return onGeographicalRegionUpdated(region.category.name, region.countryCategory?.name, region.category.category, radius, getGeoJson(geoFeatures[0].geometry))
        })
    }

    const handleOverwriteCompositeRegion = () => {
        showOverwriteCompositeRegionToast(region, (includedCategories, excludedCategories) =>
            onCompositeRegionUpdated(region.category.name, region.category.category, includedCategories, excludedCategories))
    }

    return region ? (
        <div className="relative bg-white rounded-xl shadow-md max-w-xl mx-auto p-3 w-full">
            <ul className="space-y-0.5 mt-2">
                {Object.entries(regionProperties).filter(([key]) => regionProperties[key]).map(([key, value]) => (
                    <li
                        key={key}
                        className="text-gray-700">
                        <span className="font-semibold">
                            {key}:
                        </span>
                        {" "}
                        <span>
                            {value}
                        </span>
                    </li>
                ))}
            </ul>
            <ul className="flex justify-end gap-1 mt-3">
                {region.geoJson ? (
                    <>
                        {onRegionVisualized && (
                            <li>
                                <button
                                    onClick={() => onRegionVisualized(region)}
                                    className="p-1 rounded text-green-600 hover:bg-gray-100 transition-colors">
                                    <Map size={16} />
                                </button>
                            </li>
                        )}
                        {region.geoJson?.geometry?.type !== "Point" && (
                            <>
                                <li>
                                    <button
                                        onClick={handleCopyGeoJsonToClipboard}
                                        className="p-1 rounded text-green-600 hover:bg-gray-100 transition-colors">
                                        <Copy size={16} />
                                    </button>
                                </li>
                                {onGeographicalRegionUpdated && (
                                    <li>
                                        <button
                                            onClick={handleOverwriteGeographicalRegion}
                                            className="p-1 rounded text-green-600 hover:bg-gray-100 transition-colors">
                                            <Wrench size={16} />
                                        </button>
                                    </li>
                                )}
                            </>
                        )}
                    </>
                ) : (
                    <>
                        {onCompositeRegionUpdated && (
                            <li>
                                <button
                                    onClick={handleOverwriteCompositeRegion}
                                    className="p-1 rounded text-green-600 hover:bg-gray-100 transition-colors">
                                    <Wrench size={16} />
                                </button>
                            </li>
                        )}
                    </>
                )}
            </ul>
        </div>
    ) : (
        <LoadingCard />
    )
}
