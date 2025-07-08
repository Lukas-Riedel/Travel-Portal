import CandidatePlaceCard from "./CandidatePlaceCard"

const loadingPlacesCount = 5

export default function CandidatePlaceCardGrid({ places, onCandidatePlaceRemoved }) {
    return (
        <div className="relative w-full my-4">
            <div className="grid grid-cols-[repeat(auto-fill,minmax(11rem,1fr))] gap-4 text-sm w-full">
                {places?.sort((a, b) => a.distance - b.distance)?.map((place, idx) => (
                    <CandidatePlaceCard
                        key={idx}
                        place={place}
                        onCandidatePlaceRemoved={onCandidatePlaceRemoved} />
                )) ?? Array.from({ length: loadingPlacesCount }).map((_, idx) => (
                    <CandidatePlaceCard key={idx} />
                ))}
            </div>
        </div>
    )
}
