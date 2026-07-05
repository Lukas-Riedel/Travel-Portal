import { useState, useEffect, useCallback } from "react"
import LoadingCard from "./LoadingCard.tsx"
import { cn } from "../utils/formattingUtils.ts"

interface CardGridProps {
    rowSize: number
    children?: React.ReactNode
    className?: string
}

export default function CardGrid({ rowSize, children, className = "my-6" }: CardGridProps) {
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

    const [gridTemplatecolumns, setGridTemplateColumns] = useState(() => getRealRowSize(window.innerWidth))

    useEffect(() => {
        const handleResize = () => setGridTemplateColumns(getRealRowSize(window.innerWidth))
        window.addEventListener("resize", handleResize)
        return () => window.removeEventListener("resize", handleResize)
    }, [getRealRowSize, window.innerWidth])

    return (
        <div
            className={cn("grid gap-4 text-sm w-full", className)}
            style={{ gridTemplateColumns: `repeat(${gridTemplatecolumns}, minmax(0, 1fr))` }}>
            {children || Array.from({ length: gridTemplatecolumns }, (_, index) => (
                <LoadingCard key={index} />
            ))}
        </div>
    )
}
