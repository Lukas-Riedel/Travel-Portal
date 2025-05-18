export default function TripBar({ trips }) {
    if (trips.length === 0) {
        return (
            <hr className="w-full h-0.5 bg-gradient-to-r from-transparent via-gray-400 to-transparent" />
        )
    }

    return (
        <div className="flex flex-col lg:flex-row justify-center gap-3 px-4 my-4">
            {[...trips].reverse().map((trip, index) => (
                <a
                    key={index}
                    className="flex w-full lg:w-auto text-center items-center justify-center px-4 py-2 bg-white rounded-lg shadow text-sm font-medium hover:bg-gray-100 transition"
                    href={`/trip/${trip.id}`}>
                    {trip.name + " " + trip.year}
                </a>
            ))}
        </div>
    )
}