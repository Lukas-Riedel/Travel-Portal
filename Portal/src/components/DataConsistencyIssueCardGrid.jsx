import CardGrid from "./CardGrid.tsx"
import DataConsistencyIssueCard from "./DataConsistencyIssueCard"

export default function DataConsistencyIssueCardGrid({ dataConsistencyIssues, airlines, onAirlineCodeAssigned, onFitnessReplaced, onAirportNameChanged, onAirlineLogoChanged,
    onAllAlbumsInvalidated, onPhotoInvalidated, onGeographicalExtensionCategoryAdded, onPlaceRemoved, onFlightLogged, onCategoryMetadataChanged, onAirportCountryChanged,
    onPlaceCountryChanged }) {
    return (
        <CardGrid rowSize={4}>
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
