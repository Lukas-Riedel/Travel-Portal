import { useState, useEffect } from "react"
import LoadingCard from "./LoadingCard"

export default function CardGrid({ cardsPerRowCount, children }) {
    const getRealCardsPerRowCount = width => {
        if (width < 640) {
            return Math.min(1, cardsPerRowCount)
        }
        if (width < 768) {
            return Math.min(2, cardsPerRowCount)
        }
        if (width < 1024) {
            return Math.min(3, cardsPerRowCount)
        }
        return cardsPerRowCount
    }

    const [cols, setCols] = useState(() => getRealCardsPerRowCount(window.innerWidth))

    useEffect(() => {
        const handleResize = () => setCols(getRealCardsPerRowCount(window.innerWidth))
        window.addEventListener("resize", handleResize)
        return () => window.removeEventListener("resize", handleResize)
    }, [cardsPerRowCount])

    return (
        <div
            className="grid gap-4 text-sm w-full my-4"
            style={{ gridTemplateColumns: `repeat(${cols}, minmax(0, 1fr))` }}>
            {children || Array.from({ length: cols }, (_, index) => (
                <LoadingCard key={index} />
            ))}
        </div>
    )
}
