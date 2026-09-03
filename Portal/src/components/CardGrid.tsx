import { useState, useEffect, useCallback, Children } from "react"
import LoadingCard from "./LoadingCard.tsx"
import { cn } from "../utils/formattingUtils.ts"
import { ArrowLeft, ArrowRight } from "lucide-react"

interface CardGridProps {
    children?: React.ReactNode
    rowSize: number
    columnSize?: number
    className?: string
}

export default function CardGrid({ children, rowSize, columnSize, className = "my-6" }: CardGridProps) {
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

    const [gridTemplateColumns, setGridTemplateColumns] = useState(() => getRealRowSize(window.innerWidth))
    const [currentPage, setCurrentPage] = useState(1)

    useEffect(() => {
        const handleResize = () => setGridTemplateColumns(getRealRowSize(window.innerWidth))
        window.addEventListener("resize", handleResize)
        return () => window.removeEventListener("resize", handleResize)
    }, [getRealRowSize, window.innerWidth])

    const childrenArray = Children.toArray(children)
    const totalPages = columnSize ? Math.ceil(childrenArray.length / (rowSize * columnSize)) : 1

    useEffect(() => {
        setCurrentPage(1)
    }, [childrenArray.length, rowSize, columnSize])

    return (
        <>
            <div
                className={cn("grid gap-4 text-sm w-full", className)}
                style={{ gridTemplateColumns: `repeat(${gridTemplateColumns}, minmax(0, 1fr))` }}>
                {(columnSize && childrenArray.length > 0 ? childrenArray.slice((currentPage - 1) * rowSize * columnSize, currentPage * rowSize * columnSize) : children) || Array.from({ length: gridTemplateColumns }, (_, index) => (
                    <LoadingCard key={index} />
                ))}
            </div>
            {columnSize && totalPages > 1 && (
                <div className="flex justify-center items-stretch gap-1 mt-4 flex-wrap">
                    <button
                        onClick={() => setCurrentPage(currentPage - 1)}
                        disabled={currentPage === 1}
                        className="flex items-center px-3 rounded text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
                        <ArrowLeft size={12} />
                    </button>
                    {Array.from({ length: totalPages }, (_, i) => i + 1).map(page => (
                        <button
                            key={page}
                            onClick={() => setCurrentPage(page)}
                            className={cn(
                                "px-3 py-1.5 rounded text-sm font-medium transition-colors",
                                page === currentPage ? "bg-blue-700 text-white" : "text-gray-700 hover:bg-gray-100")}>
                            {page}
                        </button>
                    ))}
                    <button
                        onClick={() => setCurrentPage(currentPage + 1)}
                        disabled={currentPage === totalPages}
                        className="flex items-center px-3 rounded text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
                        <ArrowRight size={12} />
                    </button>
                </div>
            )}
        </>
    )
}
