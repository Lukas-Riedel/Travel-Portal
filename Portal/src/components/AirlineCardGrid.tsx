import type { Airline } from "../types/CoreSwaggerTypes.ts"
import AirlineCard from "./AirlineCard.tsx"
import CardGrid from "./CardGrid.tsx"

interface AirlineCardGridProps {
    airlines: Airline[] | null
    rowSize: number
    columnSize?: number
    onAirlineNameUpdated?: (airlineId: string, name: string) => Promise<Airline>
    onAirlineLogoUpdated?: (airlineId: string, logo: string) => Promise<Airline>
    onAirlineRemoved?: (airlineId: string,) => Promise<void>
    onAirlineCodeRemoved?: (airlineId: string, code: string) => Promise<void>
}

export default function AirlineCardGrid({ airlines, rowSize, columnSize, onAirlineNameUpdated, onAirlineLogoUpdated, onAirlineRemoved, onAirlineCodeRemoved }: AirlineCardGridProps) {
    return (
        <CardGrid
            rowSize={rowSize}
            columnSize={columnSize}>
            {airlines?.map(airline => (
                <AirlineCard
                    key={airline.id}
                    airline={airline}
                    onAirlineNameUpdated={onAirlineNameUpdated ? name => onAirlineNameUpdated(airline.id, name) : undefined}
                    onAirlineLogoUpdated={onAirlineLogoUpdated ? name => onAirlineLogoUpdated(airline.id, name) : undefined}
                    onAirlineRemoved={onAirlineRemoved ? () => onAirlineRemoved(airline.id) : undefined}
                    onAirlineCodeRemoved={onAirlineCodeRemoved ? name => onAirlineCodeRemoved(airline.id, name) : undefined} />
            ))}
        </CardGrid>
    )
}