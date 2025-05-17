import DateTile from "./DateTile"

export default function DateTileGrid({ place }) {
    if (place.dates.length === 0) {
        return null
    }

    return (
        <div
            className="grid gap-4 justify-center my-4"
            style={{ gridTemplateColumns: "repeat(auto-fit, minmax(350px, 1fr))" }}>
            {[...place.dates].reverse().map((date, index) => (
                <DateTile
                    key={index}
                    place={place}
                    date={date} />
            ))}
        </div>
    )
}