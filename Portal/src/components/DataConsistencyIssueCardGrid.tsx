import type { Place } from "../classes/Place.ts"
import type { Airline, Airport, Category, CategoryMetadata, DataConsistencyIssue, Fitness, Flight, GeographicalRegion } from "../types/CoreSwaggerTypes.ts"
import CardGrid from "./CardGrid.tsx"
import DataConsistencyIssueCard from "./DataConsistencyIssueCard.tsx"

interface DataConsistencyIssueCardGridProps {
    dataConsistencyIssues: DataConsistencyIssue[] | null
    airlines: Airline[] | null
    rowSize: number
    columnSize?: number
    onAirlineCodeAssigned?: (airlineId: string, code: string) => Promise<Airline>
    onFitnessReplaced?: (timestamp: number, steps: number, seconds: number, distance: number, overwrite: boolean) => Promise<Fitness>
    onAirportNameChanged?: (airportId: string, longName: string) => Promise<Airport>
    onAirlineLogoChanged?: (airportId: string, country: string) => Promise<Airline>
    onAllAlbumsInvalidated?: () => Promise<void>
    onPhotoInvalidated?: (photoId: string) => Promise<void>
    onGeographicalExtensionCategoryAdded?: (name: string, country: string, category: string, latitude: number, longitude: number) => Promise<GeographicalRegion>
    onPlaceRemoved?: (placeId: string) => Promise<void>
    onFlightLogged?: (flight: string, from: string, to: string, scheduledDeparture: number, scheduledArrival?: number, actualDeparture?: number, actualArrival?: number, fromCode?: string, toCode?: string, aircraft?: string, registration?: string) => Promise<Flight>
    onCategoryMetadataChanged?: (categoryId: string, metadata: CategoryMetadata) => Promise<Category>
    onAirportCountryChanged?: (airportId: string, country: string) => Promise<Airport>
    onPlaceCountryChanged?: (placeId: string, country: string) => Promise<Place>
}

export default function DataConsistencyIssueCardGrid({ dataConsistencyIssues, airlines, rowSize, columnSize, onAirlineCodeAssigned, onFitnessReplaced, onAirportNameChanged, onAirlineLogoChanged,
    onAllAlbumsInvalidated, onPhotoInvalidated, onGeographicalExtensionCategoryAdded, onPlaceRemoved, onFlightLogged, onCategoryMetadataChanged, onAirportCountryChanged,
    onPlaceCountryChanged }: DataConsistencyIssueCardGridProps) {
    return (
        <CardGrid
            rowSize={rowSize}
            columnSize={columnSize}>
            {dataConsistencyIssues?.map((dataConsistencyIssue, idx) => (
                <DataConsistencyIssueCard
                    key={idx}
                    dataConsistencyIssue={dataConsistencyIssue}
                    airlines={airlines}
                    onAirlineCodeAssigned={onAirlineCodeAssigned}
                    onFitnessReplaced={onFitnessReplaced}
                    onAirportNameChanged={onAirportNameChanged}
                    onAirlineLogoChanged={onAirlineLogoChanged}
                    onAllAlbumsInvalidated={onAllAlbumsInvalidated}
                    onPhotoInvalidated={onPhotoInvalidated}
                    onGeographicalExtensionCategoryAdded={onGeographicalExtensionCategoryAdded}
                    onPlaceRemoved={onPlaceRemoved}
                    onFlightLogged={onFlightLogged}
                    onCategoryMetadataChanged={onCategoryMetadataChanged}
                    onAirportCountryChanged={onAirportCountryChanged}
                    onPlaceCountryChanged={onPlaceCountryChanged} />
            ))}
        </CardGrid>
    )
}
