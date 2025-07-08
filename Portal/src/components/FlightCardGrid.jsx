import FlightCard from "./FlightCard";

const loadingFlightsCount = 4

export default function FlightCardGrid({ flights }) {
    return (
        <div className="relative w-full my-4">
            <div className="grid grid-cols-[repeat(auto-fill,minmax(13rem,1fr))] gap-4 text-sm w-full">
                {flights?.map((flight, idx) => (
                    <FlightCard
                        key={idx}
                        flight={flight} />
                )) ?? (
                        Array.from({ length: loadingFlightsCount }).map((_, idx) => (
                            <FlightCard key={idx} />
                        ))
                    )}
            </div>
        </div>
    )
}