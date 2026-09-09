import type { Feature, GeoJSON } from "geojson"

import type { GeographicalRegion } from "../types/CoreSwaggerTypes"
import { tryExtractPointCoordinates } from "../utils/geocodingUtils"
import Map from "./Map"

const DEFAULT_POINT_COLOR = "#4285F4"

interface RegionMapProps {
    regions: GeographicalRegion[] | null
    onClick?: (region?: GeographicalRegion) => Promise<void>
}

export default function RegionMap({ regions, onClick }: RegionMapProps) {
    const points = regions?.map(region => {
        const coordinates = tryExtractPointCoordinates(region.geoJson as GeoJSON)
        return coordinates ? {
            name: region.category.name,
            latitude: coordinates.latitude,
            longitude: coordinates.longitude,
            color: DEFAULT_POINT_COLOR
        } : null
    })?.filter((p): p is NonNullable<typeof p> => p != null)

    const geoJsons = regions?.map(region => region.geoJson as GeoJSON)
        ?.filter(geoJson => tryExtractPointCoordinates(geoJson) === null)

    return (
        <Map
            points={points}
            geoJsons={geoJsons}
            onClick={onClick ? featureId => onClick(regions?.find(region => (region.geoJson as Feature).properties?.id === featureId)) : undefined} />
    )
}