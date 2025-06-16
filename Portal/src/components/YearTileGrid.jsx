import TileGrid from "./TileGrid.jsx"
import YearTile from "./YearTile.jsx"

export default function YearTileGrid({ years }) {
    return (!years || years.length > 0) && (
        <TileGrid>
            {years?.map((year, index) => (
                <YearTile
                    key={index}
                    year={year} />
            ))}
        </TileGrid>
    )
}