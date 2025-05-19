import DateTile from "./DateTile"
import TileGrid from "./TileGrid"

export default function DateTileGrid({ place }) {
    return (
        <TileGrid tiles={[...place.dates].reverse().map((date, index) => (
            <DateTile
                key={index}
                place={place}
                date={date} />
        ))} />
    )
}