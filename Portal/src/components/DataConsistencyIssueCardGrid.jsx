import CardGrid from "./CardGrid"
import DataConsistencyIssueCard from "./DataConsistencyIssueCard"

export default function DataConsistencyIssueCardGrid({ dataConsistencyIssues, airlines, onAirlineCodeAssigned,
        onAllAlbumsInvalidated, onGeographicalExtensionCategoryAdded, onPlaceRemoved, onFlightLogged, onRegionManagementOpened }) {
    return (
        <CardGrid cardsPerRowCount={4}>
            {dataConsistencyIssues?.map((dataConsistencyIssue, idx) => (
                <DataConsistencyIssueCard
                    key={idx}
                    dataConsistencyIssue={dataConsistencyIssue}
                    airlines={airlines}
                    onAirlineCodeAssigned={onAirlineCodeAssigned}
                    onAllAlbumsInvalidated={onAllAlbumsInvalidated}
                    onGeographicalExtensionCategoryAdded={onGeographicalExtensionCategoryAdded}
                    onPlaceRemoved={onPlaceRemoved}
                    onFlightLogged={onFlightLogged}
                    onRegionManagementOpened={onRegionManagementOpened} />
            ))}
        </CardGrid>
    )
}
