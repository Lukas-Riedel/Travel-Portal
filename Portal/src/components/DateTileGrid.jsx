import DateTile from "./DateTile"
import TileGrid from "./TileGrid"

export default function DateTileGrid({ place, onAlbumRefreshed }) {
    return (!place?.dates || place.dates.length > 0) && (
        <TileGrid tiles={(place?.dates && [...place.dates])?.reverse()?.filter(date => date?.album)?.map((date, index) => (
            <DateTile
                key={index}
                place={place}
                date={date}
                onAlbumRefreshed={onAlbumRefreshed} />
        ))} />
    )
}