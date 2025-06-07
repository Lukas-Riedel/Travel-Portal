import { useEffect, useRef, useState } from "react"
import { formatStatisticsName, formatStatisticsUnit } from "../utils/formatters.js"
import { useConfiguration } from "../contexts/ConfigContext"
import { decapitalize } from "../utils/helpers.js"
import { TailSpin } from "react-loader-spinner"

const loadingStatisticsCount = 20

export default function StatisticsPanel({ statistics }) {
    const configuration = useConfiguration()

    const containerRef = useRef(null)
    const isDraggingRef = useRef(false)
    const dragStartX = useRef(0)
    const scrollStartX = useRef(0)
    const animationFrame = useRef(null)
    const [shuffledStatistics, setShuffledStatistics] = useState(() => statistics && [...statistics].sort(() => Math.random() - 0.5))

    useEffect(() => {
        if (statistics) {
            setShuffledStatistics([...statistics].sort(() => Math.random() - 0.5))
        }
    }, [statistics])

    useEffect(() => {
        let lastTimestamp = null
        let cancelled = false

        function step(timestamp) {
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

    function onWindowPointerMove(e) {
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

    function onWindowPointerUp(e) {
        isDraggingRef.current = false

        window.removeEventListener("pointermove", onWindowPointerMove)
        window.removeEventListener("pointerup", onWindowPointerUp)
        window.removeEventListener("pointercancel", onWindowPointerUp)
    }

    function onPointerDown(e) {
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

    const doubledStats = shuffledStatistics && [...shuffledStatistics, ...shuffledStatistics]
    return (!doubledStats || doubledStats.length > 0) && (
        <div
            className="overflow-hidden py-2 my-2 relative bg-white"
            ref={containerRef}
            onPointerDown={onPointerDown}
            style={{ touchAction: "none" }}>
            <div className="flex gap-4 px-2 items-stretch">
                {(doubledStats ?? Array.from({ length: loadingStatisticsCount })).map((stat, idx) => (
                    <div
                        key={idx}
                        className="flex flex-col bg-white text-black px-6 py-3 rounded-xl min-w-[130px] text-center flex-shrink-0 shadow-sm select-none border border-gray-200">
                        {doubledStats ? (
                            <>
                                <div className="text-sm mb-1.5 tracking-wide font-medium">
                                    {formatStatisticsName(stat?.name)}
                                </div>
                                <div className="flex-grow flex items-center justify-center">
                                    {Array.isArray(stat?.value) ? (
                                        <ol className="list-decimal list-inside space-y-1 text-xs text-gray-700">
                                            {stat.value.map((item, i) => (
                                                <li key={i}>
                                                    <span>{item.key}</span>{" "}
                                                    <span className="text-gray-400">
                                                        ({decapitalize(formatStatisticsUnit(stat?.unit, item?.value, configuration?.mainCurrency ?? "???"))})
                                                    </span>
                                                </li>
                                            ))}
                                        </ol>
                                    ) : (
                                        <div className="text-lg">
                                            {formatStatisticsUnit(stat?.unit, stat?.value, configuration?.mainCurrency ?? "???")}
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
