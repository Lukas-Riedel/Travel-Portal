import CardGrid from "./CardGrid";
import FlightCard from "./FlightCard";

export default function FlightCardGrid({ flights }) {
    return (
        <CardGrid cardsPerRowCount={4}>
            {flights?.map(flight => (
                <FlightCard
                    key={flight.start}
                    flight={flight} />
            ))}
        </CardGrid>
    )
}