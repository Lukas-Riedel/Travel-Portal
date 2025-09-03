import AirlineCard from "./AirlineCard";
import CardGrid from "./CardGrid";

export default function AirlineCardGrid({ airlines, onAirlineNameUpdated, onAirlineLogoUpdated, onAirlineRemoved, onAirlineCodeRemoved }) {
    return (
        <CardGrid cardsPerRowCount={6}>
            {airlines?.map(airline => (
                <AirlineCard
                    key={airline.id}
                    airline={airline}
                    onAirlineNameUpdated={onAirlineNameUpdated}
                    onAirlineLogoUpdated={onAirlineLogoUpdated}
                    onAirlineRemoved={onAirlineRemoved}
                    onAirlineCodeRemoved={onAirlineCodeRemoved} />
            ))}
        </CardGrid>
    )
}