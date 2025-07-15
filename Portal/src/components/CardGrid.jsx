import LoadingCard from "./LoadingCard"

export default function CardGrid({ cardsPerRowCount, children }) {
    return (
        <div
            className="grid gap-4 text-sm w-full my-4"
            style={{
                gridTemplateColumns: `repeat(${cardsPerRowCount}, minmax(0, 1fr))`
            }}>
            {children || Array.from({ length: cardsPerRowCount }, (_, index) => (
                <LoadingCard key={index} />
            ))}
        </div>
    )
}