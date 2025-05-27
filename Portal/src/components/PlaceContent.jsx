import PlaceMap from "./PlaceMap.jsx"

export default function PlaceContent({ place }) {
    return (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            <>
                <p className="text-gray-700 text-justify leading-relaxed">
                    {place.excerpt}
                </p>
            </>
            <div className="w-full h-full overflow-hidden rounded-lg shadow">
                <PlaceMap places={[place]} />
            </div>
        </div>
    )
}