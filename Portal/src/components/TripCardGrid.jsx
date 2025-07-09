import TripCard from "./TripCard"

const loadingTripsCount = 3

export default function TripCardGrid({ trips, onTripRemoved }) {
    return (
        <div className="relative w-full my-4">
            <div className="grid grid-cols-[repeat(auto-fill,minmax(18rem,1fr))] gap-4 text-sm w-full">
                {trips?.map((trip, idx) => (
                    <TripCard
                        key={idx}
                        trip={trip}
                        onTripRemoved={onTripRemoved} />
                )) ?? Array.from({ length: loadingTripsCount }).map((_, idx) => (
                    <TripCard key={idx} />
                ))}
            </div>
        </div>
    )
}
