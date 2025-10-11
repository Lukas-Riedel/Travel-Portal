import LoadingCard from "./LoadingCard"
import { formatKilometers } from "../utils/formatters"
import { MapIcon } from "lucide-react"

export default function RegionCard({ region, onCategorySelected, onRegionVisualized }) {
    const regionProperties = region && {
        "Typ": region.geoJson ? "Geografický" : "Kompozitní",
        "Rádius": region.radius > 0 && formatKilometers(region.radius),
        "Stát": region.countryCategory && region.countryCategory.name,
        "Podtyp": region.geoJson?.geometry?.type && (region.geoJson?.geometry?.type === "Point" ? "Bod" : "Oblast"),
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
            {onRegionVisualized && region.geoJson && (
                <button
                    onClick={() => onRegionVisualized(region)}
                    className="absolute bottom-2 right-2 p-1 rounded text-green-600 hover:bg-gray-100 transition-colors">
                    <MapIcon size={16} />
                </button>
            )}
        </div>
    ) : (
        <LoadingCard />
    )
}
