import { useEffect, useState } from "react"
import SunCalc from "suncalc"

export default function SunAltitudeIcon({ altitude, place }) {
    const [time, setTime] = useState(null)

    const isSunrise = Object.is(altitude, +0)
    const isSunset = Object.is(altitude, -0)
    const isPositiveRateAltitude = altitude > 0 || isSunrise

    useEffect(() => {
        const today = new Date()
        const sunTimes = SunCalc.getTimes(today, place.latitude, place.longitude)

        const sampled = Array.from({ length: 24 * 60 }, (_, i) => {
            const time = new Date(+(isNaN(sunTimes.sunrise) ? new Date().setHours(0, 0, 0, 0) : sunTimes.sunrise) + i * 2 * 60 * 1000)
            const altitude = (SunCalc.getPosition(time, place.latitude, place.longitude).altitude * 180) / Math.PI
            return { time, altitude }
        })

        for (let i = 1; i < sampled.length; i++) {
            const previous = sampled[i - 1]
            const current = sampled[i]

            if ((isPositiveRateAltitude && previous.altitude < Math.abs(altitude) && current.altitude >= Math.abs(altitude))
                || (!isPositiveRateAltitude && previous.altitude > Math.abs(altitude) && current.altitude <= Math.abs(altitude))) {
                const ratio = (Math.abs(altitude) - previous.altitude) / (current.altitude - previous.altitude)
                const time = new Date(previous.time.getTime() + ratio * (current.time - previous.time))
                setTime(time)
            }
        }
    }, [place])

    const icon = isSunrise ? "🌅" : isSunset ? "🌇" : "☀️"
    const label = isSunrise ? "Východ" : isSunset ? "Západ" : Math.abs(altitude) + "°"

    return (
        <div className="flex flex-col items-center">
            <div className="text-xl">{icon}</div>
            <div className="text-sm text-gray-500">{label}</div>
            <div className="font-semibold">{time ? time.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit", timeZone: place.timezone }) : "---"}</div>
        </div>
    )
}