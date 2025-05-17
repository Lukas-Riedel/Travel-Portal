export default function TripBar({ trips }) {
    if (trips.length === 0) {
        return null
    }

    return (
        <div className="flex flex-col lg:flex-row justify-center gap-3 px-4 my-4">
            {trips.map((trip, index) => (
                <a
                    key={index}
                    className="flex w-full lg:w-auto text-center items-center justify-center px-4 py-2 bg-white rounded-lg shadow text-sm font-medium hover:bg-gray-100 transition"
                    href={`index/${trip.id}`}>
                    {trip.name + " " + trip.year}
                </a>
            ))}
        </div>
    )
}