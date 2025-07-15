import { useState, useEffect } from "react"
import LoadingCard from "./LoadingCard"

export default function CardGrid({ cardsPerRowCount, children }) {
    const [cols, setCols] = useState(cardsPerRowCount)

    useEffect(() => {
        const updateCols = () => {
            const width = window.innerWidth
            if (width < 640) {
                setCols(Math.min(1, cardsPerRowCount))
            }
            else if (width < 768) {
                setCols(Math.min(2, cardsPerRowCount))
            }
            else if (width < 1024) {
                setCols(Math.min(3, cardsPerRowCount))
            }
            else {
                setCols(cardsPerRowCount)
            }
        }

        updateCols()
        window.addEventListener("resize", updateCols)
        return () => window.removeEventListener("resize", updateCols)
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
