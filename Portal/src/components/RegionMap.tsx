import { useMemo } from "react"
import Map from "./Map"
import type { GeographicalRegion } from "../types/CoreSwaggerTypes"
import type { GeoJSON, Feature, Geometry } from "geojson"
import { tryExtractPointCoordinates } from "../utils/geocodingUtils"

const DEFAULT_POINT_COLOR = "#4285F4"

interface RegionMapProps {
    regions: GeographicalRegion[] | null
    onClick?: (featureId?: string) => Promise<void>
}

export default function RegionMap({ regions, onClick }: RegionMapProps) {
    const points = useMemo(() => regions?.map(region => {
        const coordinates = tryExtractPointCoordinates(region.geoJson as GeoJSON)
        return coordinates && {
            name: region.category.name,
            latitude: coordinates.latitude,
            longitude: coordinates.longitude,
            color: DEFAULT_POINT_COLOR
        }
    })?.filter(Boolean), [regions])

    const geoJsons = useMemo(() => regions?.map(region => region.geoJson as GeoJSON)
        ?.filter(geoJson => tryExtractPointCoordinates(geoJson) === null), [regions])

    return (
        <Map
            points={points}
            geoJsons={geoJsons}
            onClick={onClick} />
    )
}