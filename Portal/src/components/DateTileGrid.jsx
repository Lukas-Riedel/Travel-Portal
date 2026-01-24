import { getCurrentOrMaximumAllowedTimestamp } from "../utils/timeUtils.ts"
import DateTile from "./DateTile"
import TileGrid from "./TileGrid"

export default function DateTileGrid({ place, onAlbumRefreshed }) {
    return (!place?.dates || place.dates.length > 0) && (
        <TileGrid>
            {(place && (() => {
                const seen = new Set()
                return [...(place.dates ?? [])].reverse().filter(date => date?.album && !seen.has(date.album.id) && seen.add(date.album.id))
            })())?.filter(date => date.end < getCurrentOrMaximumAllowedTimestamp())?.map(date => (
                <DateTile
                    key={date.start}
                    place={place}
                    date={date}
                    onAlbumRefreshed={onAlbumRefreshed}
                />
            ))}
        </TileGrid>
    )
}
