import { useEffect, useRef, useState } from "react"
import { useConfiguration } from "../contexts/ConfigContext.tsx"
import { TailSpin } from "react-loader-spinner"
import { useFormatters } from "../hooks/useFormatters.ts"
import type { Statistics } from "../types/CoreSwaggerTypes.ts"
import { useTranslation } from "react-i18next"

const LOADING_STATISTICS_COUNT = 20
const MAX_STATISTICS_VALUES_COUNT = 5

interface StatisticsPanelProps {
    statistics: Statistics[] | null
}

export default function StatisticsPanel({ statistics }: StatisticsPanelProps) {
    const { t } = useTranslation()
    const { configuration } = useConfiguration()
    const { formatStatisticsUnit } = useFormatters()

    const createShuffledStatistics = () => [...statistics].sort(() => Math.random() - 0.5)

    const containerRef = useRef<HTMLDivElement | null>(null)
    const isDraggingRef = useRef(false)
    const dragStartX = useRef(0)
    const scrollStartX = useRef(0)
    const animationFrame = useRef<number | null>(null)
    const [shuffledStatistics, setShuffledStatistics] = useState(() => statistics && createShuffledStatistics())

    useEffect(() => {
        if (statistics) {
            setShuffledStatistics(createShuffledStatistics())
        }
    }, [statistics])

    useEffect(() => {
        let lastTimestamp: number | null = null
        let cancelled = false

        const step = (timestamp: number) => {
            if (cancelled || !containerRef.current) {
                return
            }

            if (!lastTimestamp) {
                lastTimestamp = timestamp
            }

            const delta = timestamp - lastTimestamp
            lastTimestamp = timestamp

            if (!isDraggingRef.current) {
                containerRef.current.scrollLeft += 0.7 * (delta / 16)

                const halfScrollWidth = containerRef.current.scrollWidth / 2
                if (containerRef.current.scrollLeft >= halfScrollWidth) {
                    containerRef.current.scrollLeft -= halfScrollWidth
                }
            }

            animationFrame.current = requestAnimationFrame(step)
        }

        animationFrame.current = requestAnimationFrame(step)

        return () => {
            cancelled = true
            if (animationFrame.current) {
                cancelAnimationFrame(animationFrame.current)
            }
        }
    }, [])

    const onWindowPointerMove = (e: PointerEvent) => {
        if (!isDraggingRef.current) {
            return
        }

        if (!containerRef.current) {
            return
        }

        const dx = dragStartX.current - e.clientX
        containerRef.current.scrollLeft = scrollStartX.current + dx

        const halfScrollWidth = containerRef.current.scrollWidth / 2
        if (containerRef.current.scrollLeft <= 0) {
            containerRef.current.scrollLeft += halfScrollWidth
            scrollStartX.current += halfScrollWidth
            dragStartX.current = e.clientX
        }
        else if (containerRef.current.scrollLeft >= halfScrollWidth) {
            containerRef.current.scrollLeft -= halfScrollWidth
            scrollStartX.current -= halfScrollWidth
            dragStartX.current = e.clientX
        }
    }

    const onWindowPointerUp = () => {
        isDraggingRef.current = false

        window.removeEventListener("pointermove", onWindowPointerMove)
        window.removeEventListener("pointerup", onWindowPointerUp)
        window.removeEventListener("pointercancel", onWindowPointerUp)
    }

    const onPointerDown = (e: React.PointerEvent<HTMLDivElement>) => {
        if (!containerRef.current) {
            return
        }

        isDraggingRef.current = true

        dragStartX.current = e.clientX
        scrollStartX.current = containerRef.current.scrollLeft

        window.addEventListener("pointermove", onWindowPointerMove)
        window.addEventListener("pointerup", onWindowPointerUp)
        window.addEventListener("pointercancel", onWindowPointerUp)

        e.preventDefault()
    }

    const decapitalize = (str: string) => str[0].toLowerCase() + str.slice(1)

    const doubledStats = shuffledStatistics && [...shuffledStatistics, ...shuffledStatistics]

    return (!doubledStats || doubledStats.length > 0) && (
        <div
            className="overflow-hidden py-2 my-2 relative bg-white"
            ref={containerRef}
            onPointerDown={onPointerDown}
            style={{ touchAction: "none" }}>
            <div className="flex gap-4 px-2 items-stretch">
                {(doubledStats ?? Array.from({ length: LOADING_STATISTICS_COUNT })).map((stat, index) => (
                    <div
                        key={index}
                        className="flex flex-col bg-white text-black px-6 py-3 rounded-xl min-w-[130px] text-center flex-shrink-0 shadow-md select-none border border-gray-200">
                        {doubledStats ? (
                            <>
                                <div className="text-sm mb-1.5 tracking-wide font-medium">
                                    {t(`statistics.name.${stat.name}`)}
                                </div>
                                <div className="flex-grow flex items-center justify-center">
                                    {Array.isArray(stat.value) ? (
                                        <ol className="list-decimal list-inside space-y-1 text-xs text-gray-700">
                                            {stat.value.slice(0, MAX_STATISTICS_VALUES_COUNT).map((item, index) => (
                                                <li key={index}>
                                                    <span>
                                                        {item.key}
                                                    </span>
                                                    {" "}
                                                    <span className="text-gray-400">
                                                        ({decapitalize(formatStatisticsUnit(stat.unit, Number(item.value), configuration?.expensify?.mainCurrency))})
                                                    </span>
                                                </li>
                                            ))}
                                        </ol>
                                    ) : (
                                        <div className="text-lg">
                                            {formatStatisticsUnit(stat.unit, Number(stat.value), configuration?.expensify?.mainCurrency)}
                                        </div>
                                    )}
                                </div>
                            </>
                        ) : (
                            <div className="flex-grow flex items-center justify-center">
                                <div className="items-center justify-center flex h-[120px]">
                                    <TailSpin
                                        color="black"
                                        height={30}
                                        width={30} />
                                </div>
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </div>
    )
}
