import { useState, useRef, useEffect } from "react"
import SunAltitudeIcon from "./SunAltitudeIcon"
import { getCurrentTimestamp, getDate } from "../utils/timeUtils.ts"
import { format } from "date-fns"

export default function SunAltitudeBar({ place }) {
    const [date, setDate] = useState(null)
    const dateInputRef = useRef(null)

    useEffect(() => {
        if (!place?.dates) {
            return
        }

        const nextDate = place.dates.find(date => date.start > getCurrentTimestamp())
        setDate(nextDate ? getDate(nextDate.start) : new Date())
    }, [place?.dates])

    return (
        <div className="grid grid-cols-3 sm:grid-cols-6 gap-4 my-6 text-center relative">
            <input
                ref={dateInputRef}
                type="date"
                value={format(date, "yyyy-MM-dd")}
                onChange={(e) => setDate(new Date(e.target.value))}
                className="absolute opacity-0 pointer-events-none" />

            {[+0, +20, +30, -30, -20, -0].map((altitude, index) => (
                <button
                    key={index}
                    onClick={() => dateInputRef.current.showPicker()}>
                    <SunAltitudeIcon
                        place={place}
                        altitude={altitude}
                        date={date} />
                </button>
            ))}
        </div>
    )
}