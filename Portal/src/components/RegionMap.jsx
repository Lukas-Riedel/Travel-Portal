import { useMemo } from "react"
import Map from "./Map"

export default function RegionMap({ region }) {
    const points = useMemo(() => region.geoJson.geometry.type === "Point"
        ? [{ latitude: region.geoJson.geometry.coordinates[1], longitude: region.geoJson.geometry.coordinates[0] }] : undefined, [region.geoJson])

    const geoJson = useMemo(() => region.geoJson.geometry.type !== "Point" && region.geoJson, [region.geoJson])
    
    return (
        <Map
            points={points}
            geoJson={geoJson} />
    )
}