import { useMemo } from "react"
import Map from "./Map"

export default function RegionMap({ regions, onClick }) {
    const points = useMemo(() => regions?.filter(region => region.geoJson?.geometry?.type === "Point")
        ?.map(region => ({ latitude: region.geoJson.geometry.coordinates[1], longitude: region.geoJson.geometry.coordinates[0] })), [regions])
    const geoJsons = useMemo(() => regions?.filter(region => region.geoJson?.geometry?.type !== "Point")?.map(region => region.geoJson), [regions])

    return (
        <Map
            points={points}
            geoJsons={geoJsons}
            onClick={onClick} />
    )
}