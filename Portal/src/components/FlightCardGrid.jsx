import CardGrid from "./CardGrid.tsx";
import FlightCard from "./FlightCard";

export default function FlightCardGrid({ flights }) {
    return (
        <CardGrid rowSize={4}>
            {flights?.map(flight => (
                <FlightCard
                    key={flight.start}
                    flight={flight} />
            ))}
        </CardGrid>
    )
}