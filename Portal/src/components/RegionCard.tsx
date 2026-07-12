import { useMemo } from "react"
import { Copy, Map, Wrench } from "lucide-react"
import { useTranslation } from "react-i18next"
import LoadingCard from "./LoadingCard.tsx"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { useFormatters } from "../hooks/useFormatters.ts"
import type { GeographicalRegion, CompositeRegion, CategoryIdentifier } from "../types/CoreSwaggerTypes.ts"
import type { Region } from "../types/Region.ts"
import PropertyCardContent from "./PropertyCardContent.tsx"
import Card from "./Card.tsx"
import type { GeoJSON } from "geojson"
import { getGeoFeatures, getGeoJson, tryExtractPointCoordinates } from "../utils/geocodingUtils.ts"

interface RegionCardProps {
    region: Region | null
    onCategorySelected?: (category: any) => void
    onGeographicalRegionUpdated?: (nname: string, country: string, category: string, radius: number, geoJson: GeoJSON) => Promise<any>
    onCompositeRegionUpdated?: (name: string, category: string, includedRegions: string[], excludedRegions?: string[]) => Promise<any>
    onRegionVisualized?: (region: Region) => void
}

const isGeographicalRegion = (region: Region): region is GeographicalRegion => "geoJson" in region
const isGeopgraphicalExtension = (region: Region): boolean => isGeographicalRegion(region) && tryExtractPointCoordinates(region.geoJson as GeoJSON) !== null
const isCompositeRegion = (region: Region): region is CompositeRegion => "includedCategories" in region

export default function RegionCard({ region, onCategorySelected, onGeographicalRegionUpdated, onCompositeRegionUpdated, onRegionVisualized }: RegionCardProps) {
    const { t } = useTranslation()
    const { showCopyRegionGeoJsonToast, showOverwriteGeographicalRegionToast, showOverwriteCompositeRegionToast } = usePredefinedUserInput()
    const { formatKilometers } = useFormatters()

    const handleCopyGeoJsonToClipboard = () => {
        if (isGeographicalRegion(region)) {
            showCopyRegionGeoJsonToast(() => navigator.clipboard.writeText(JSON.stringify(region.geoJson)))
        }
    }

    const handleOverwriteGeographicalRegion = () => {
        if (isGeographicalRegion(region) && onGeographicalRegionUpdated) {
            showOverwriteGeographicalRegionToast(region, (radius, geoJson) => {
                const geoFeatures = getGeoFeatures(geoJson)
                if (geoFeatures.length !== 1) {
                    return Promise.reject("There must be exactly one feature in the GeoJSON, but there are " + geoFeatures.length + " features.")
                }

                return onGeographicalRegionUpdated(region.category.name, region.countryCategory?.name, region.category.category, radius, getGeoJson(geoFeatures[0].geometry))
            })
        }
    }

    const handleOverwriteCompositeRegion = () => {
        if (isCompositeRegion(region) && onCompositeRegionUpdated) {
            showOverwriteCompositeRegionToast(region, (includedCategories, excludedCategories) =>
                onCompositeRegionUpdated(region.category.name, region.category.category, includedCategories, excludedCategories))
        }
    }

    const handleCategorySelected = (category: CategoryIdentifier) => {
        if (onCategorySelected) {
            onCategorySelected(category)
        }
    }

    const properties = useMemo(() => region && ({
        [t("region.label.type")]: isGeographicalRegion(region) ? t("region.type.geographical") : t("region.type.composite"),
        [t("region.label.radius")]: isGeographicalRegion(region) && region.radius > 0 && formatKilometers(region.radius),
        [t("region.label.country")]: isGeographicalRegion(region) && region.countryCategory?.name,
        [t("region.label.subtype")]: isGeographicalRegion(region) && isGeopgraphicalExtension(region) ? t("region.subtype.point") : t("region.subtype.area"),
        [t("region.label.coordinates")]: isGeographicalRegion(region) && isGeopgraphicalExtension(region) && (region.geoJson as any).geometry.coordinates.map(coordinate => coordinate.toFixed(4)).join(", "),
        [t("region.label.includedRegions")]: isCompositeRegion(region) && region.includedCategories && (
            <ul className="space-y-0.5 list-inside list-disc">
                {region.includedCategories.map(category => (
                    <li
                        key={category.id}
                        className="ml-2 text-gray-700 hover:text-gray-500 hover:underline hover:cursor-pointer"
                        onClick={() => handleCategorySelected(category)}>
                        {category.name}
                    </li>
                ))}
            </ul>
        ),
        [t("region.label.excludedRegions")]: isCompositeRegion(region) && region.excludedCategories && (
            <ul className="space-y-0.5 list-inside list-disc">
                {region.excludedCategories.map(category => (
                    <li
                        key={category.id}
                        className="ml-2 text-gray-700 hover:text-gray-500 hover:underline hover:cursor-pointer"
                        onClick={() => handleCategorySelected(category)}>
                        {category.name}
                    </li>
                ))}
            </ul>
        )
    }), [region, t, formatKilometers, onCategorySelected])

    if (!region) {
        return (
            <LoadingCard />
        )
    }

    return (
        <Card className="relative">
            <PropertyCardContent properties={properties} />
            <ul className="flex justify-end gap-1 mt-3">
                {isGeographicalRegion(region) ? (
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
                        {!isGeopgraphicalExtension(region) && (
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
        </Card>
    )
}