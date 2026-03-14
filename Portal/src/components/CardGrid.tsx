import { useState, useEffect, useCallback } from "react"
import LoadingCard from "./LoadingCard.tsx"

interface CardGridProps {
    rowSize: number
    children?: React.ReactNode
}

export default function CardGrid({ rowSize, children }: CardGridProps) {
    // TODO: Rewrite to CSS.
    const getRealRowSize = useCallback((width: number) => {
        if (width < 640) {
            return Math.min(1, rowSize)
        }
        if (width < 768) {
            return Math.min(2, rowSize)
        }
        if (width < 1024) {
            return Math.min(3, rowSize)
        }
        return rowSize
    }, [rowSize])

    const [gridtemplatecolumns, setGridTemplateColumns] = useState(() => getRealRowSize(window.innerWidth))

    useEffect(() => {
        const handleResize = () => setGridTemplateColumns(getRealRowSize(window.innerWidth))
        window.addEventListener("resize", handleResize)
        return () => window.removeEventListener("resize", handleResize)
    }, [getRealRowSize])

    return (
        <div
            className="grid gap-4 text-sm w-full my-6"
            style={{ gridTemplateColumns: `repeat(${gridtemplatecolumns}, minmax(0, 1fr))` }}>
            {children || Array.from({ length: gridtemplatecolumns }, (_, index) => (
                <LoadingCard key={index} />
            ))}
        </div>
    )
}
