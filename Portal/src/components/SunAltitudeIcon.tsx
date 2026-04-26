import { Sun, Sunrise, Sunset } from "lucide-react"
import { useMemo } from "react"
import type { Place } from "../types/CoreSwaggerTypes.ts"
import { addMinutes, startOfDay } from "date-fns"
import { useTranslation } from "react-i18next"
import { formatTimestamp } from "../utils/timeUtils.ts"
import { getSunAltitude } from "../utils/sunUtils.ts"

interface SunAltitudeIconProps {
    place: Place | null
    altitude: number
    date?: Date
}

export default function SunAltitudeIcon({ place, altitude, date }: SunAltitudeIconProps) {
    const { t } = useTranslation()

    const isSunrise = useMemo(() => Object.is(altitude, +0), [altitude])
    const isSunset = useMemo(() => Object.is(altitude, -0), [altitude])
    const isPositiveRateAltitude = useMemo(() => altitude > 0 || isSunrise, [altitude, isSunrise])

    const time = useMemo(() => {
        if (!place) {
            return null
        }

        const baseDate = startOfDay(date || new Date())
        const sampled = Array.from({ length: 24 * 60 }, (_, minuteOffset) => {
            const time = addMinutes(baseDate, minuteOffset)
            const altitude = getSunAltitude(time, place)
            return { time, altitude }
        })

        for (let i = 1; i < sampled.length; i++) {
            const previous = sampled[i - 1]
            const current = sampled[i]

            if ((isPositiveRateAltitude && previous.altitude < Math.abs(altitude) && current.altitude >= Math.abs(altitude))
                || (!isPositiveRateAltitude && previous.altitude > Math.abs(altitude) && current.altitude <= Math.abs(altitude))) {
                const ratio = (Math.abs(altitude) - previous.altitude) / (current.altitude - previous.altitude)
                return new Date(previous.time.getTime() + ratio * (current.time.getTime() - previous.time.getTime()))
            }
        }

        return null
    }, [date, place])

    const SunIcon = useMemo(() => isSunrise ? Sunrise : isSunset ? Sunset : Sun, [isSunrise, isSunset])

    return (
        <div className="flex flex-col items-center">
            <div className="text-xl">
                <SunIcon size={24} />
            </div>
            <div className="text-sm text-gray-500 mt-1">
                {isSunrise ? t("weather.sun.sunrise") : isSunset ? t("weather.sun.sunset") : `${Math.abs(altitude)}°`}
            </div>
            <div className="font-semibold">
                {time ? formatTimestamp(time, t("general.format.time"), place?.timezone) : "---"}
            </div>
        </div>
    )
}