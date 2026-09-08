import type { GeoJSON, Point } from "geojson"
import { Copy, Map, Wrench } from "lucide-react"
import { useMemo } from "react"
import { useTranslation } from "react-i18next"

import { useFormatters } from "../hooks/useFormatters.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import type { CategoryIdentifier, CompositeRegion, GeographicalRegion } from "../types/CoreSwaggerTypes.ts"
import type { Region } from "../types/Region.ts"
import { getGeoFeatures, getGeoJson, tryExtractPointCoordinates } from "../utils/geocodingUtils.ts"
import Card from "./Card.tsx"
import LoadingCard from "./LoadingCard.tsx"
import PropertyCardContent from "./PropertyCardContent.tsx"

interface RegionCardProps {
    region: Region | null
    onCategorySelected?: (category: CategoryIdentifier) => void
    onGeographicalRegionUpdated?: (nname: string, country: string | undefined, category: string, radius: number, geoJson: GeoJSON) => Promise<GeographicalRegion>
    onCompositeRegionUpdated?: (name: string, category: string, includedRegions: string[], excludedRegions?: string[]) => Promise<CompositeRegion>
    onRegionVisualized?: (region: GeographicalRegion) => void
}

const isGeographicalRegion = (region: Region): region is GeographicalRegion => "geoJson" in region
const isGeopgraphicalExtension = (region: Region): boolean => isGeographicalRegion(region) && tryExtractPointCoordinates(region.geoJson as GeoJSON) !== null
const isCompositeRegion = (region: Region): region is CompositeRegion => "includedCategories" in region

export default function RegionCard({ region, onCategorySelected, onGeographicalRegionUpdated, onCompositeRegionUpdated, onRegionVisualized }: RegionCardProps) {
    const { t } = useTranslation()
    const { showCopyRegionGeoJsonToast, showOverwriteGeographicalRegionToast, showOverwriteCompositeRegionToast } = usePredefinedUserInput()
    const { formatKilometers } = useFormatters()

    const handleGeoJsonCopied = () => {
        if (region && isGeographicalRegion(region)) {
            showCopyRegionGeoJsonToast(() => navigator.clipboard.writeText(JSON.stringify(region.geoJson)))
        }
    }

    const handleGeographicalRegionOverwritten = () => {
        if (region && isGeographicalRegion(region) && onGeographicalRegionUpdated) {
            const geoFeature = getGeoFeatures(region.geoJson as GeoJSON)[0]

            showOverwriteGeographicalRegionToast(region, (radius, geoJson) => {
                const geoFeatures = getGeoFeatures(geoJson)
                if (geoFeatures.length !== 1) {
                    return Promise.reject("There must be exactly one feature in the GeoJSON, but there are " + geoFeatures.length + " features.")
                }

                return onGeographicalRegionUpdated(region.category.name, region.countryCategory?.name, region.category.category, radius, getGeoJson(geoFeatures[0]?.geometry ?? (geoFeature?.geometry ?? ({} as Point))))
            })
        }
    }

    const handleCompositeRegionOverwritten = () => {
        if (region && isCompositeRegion(region) && onCompositeRegionUpdated) {
            showOverwriteCompositeRegionToast(region, (includedCategories, excludedCategories) =>
                onCompositeRegionUpdated(region.category.name ?? "", region.category.category, includedCategories, excludedCategories))
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
        [t("region.label.coordinates")]: isGeographicalRegion(region) && isGeopgraphicalExtension(region) && (region.geoJson as Point).coordinates.map((coordinate: number) => coordinate.toFixed(4)).join(", "),
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
                                    className="btn-ghost-success">
                                    <Map size={16} />
                                </button>
                            </li>
                        )}
                        {!isGeopgraphicalExtension(region) && (
                            <>
                                <li>
                                    <button
                                        onClick={handleGeoJsonCopied}
                                        className="btn-ghost-success">
                                        <Copy size={16} />
                                    </button>
                                </li>
                                {onGeographicalRegionUpdated && (
                                    <li>
                                        <button
                                            onClick={handleGeographicalRegionOverwritten}
                                            className="btn-ghost-success">
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
                                    onClick={handleCompositeRegionOverwritten}
                                    className="btn-ghost-success">
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