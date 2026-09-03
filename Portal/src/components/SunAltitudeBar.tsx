import { useState, useRef, useEffect } from "react"
import SunAltitudeIcon from "./SunAltitudeIcon.tsx"
import { formatTimestamp, getCurrentTimestamp, getDate } from "../utils/timeUtils.ts"
import type { Place } from "../types/CoreSwaggerTypes.ts"

interface SunAltitudeBarProps {
    place: Place | null
}

const DISPLAYED_ALTITUDES = [+0, +20, +30, -30, -20, -0]

export default function SunAltitudeBar({ place }: SunAltitudeBarProps) {
    const [date, setDate] = useState(new Date())
    const dateInputRef = useRef(null)

    useEffect(() => {
        if (!place?.dates) {
            return
        }

        const nextDate = place.dates.find(date => date.start > getCurrentTimestamp())
        if (nextDate) {
            setDate(getDate(nextDate.start))
        }
    }, [place?.dates])

    return (
        <div
            className="grid grid-cols-3 sm:grid-cols-6 gap-4 my-6 text-center relative hover:cursor-pointer"
            onClick={() => dateInputRef.current.showPicker()}>
            <input
                ref={dateInputRef}
                type="date"
                value={formatTimestamp(date, "yyyy-MM-dd")}
                onChange={e => setDate(e.target.value ? new Date(e.target.value) : new Date())}
                className="absolute opacity-0 pointer-events-none" />
            {DISPLAYED_ALTITUDES.map((altitude, index) => (
                <SunAltitudeIcon
                    key={index}
                    place={place}
                    altitude={altitude}
                    date={date} />
            ))}
        </div>
    )
}