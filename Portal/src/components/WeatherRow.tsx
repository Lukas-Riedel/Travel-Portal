import { ArrowUpLeftFromCircle, ChevronDown, Clock, Cloud, CloudRain, CloudSun, Droplets, HelpCircle, Sun, Wind, type LucideIcon } from "lucide-react"
import type { Weather } from "../types/CoreSwaggerTypes.ts"
import { useMemo } from "react"
import Tooltip from "./Tooltip.jsx"
import { useTranslation } from "react-i18next"
import { formatTimestamp, getCurrentTimestamp } from "../utils/timeUtils.ts"
import type { Coordinates } from "../types/Coordinates.ts"
import { getSunAltitude, getSunAzimuth } from "../utils/sunUtils.ts"

interface WeatherRowProps {
    coordinates: Coordinates
    weather: Weather
    start: number
    end: number
    timezone?: string
    showTime?: boolean
    onWeatherForecastExpanded?: () => void
}

export default function WeatherRow({ coordinates, weather, start, end, timezone, showTime, onWeatherForecastExpanded }: WeatherRowProps) {
    const { t } = useTranslation()

    const { WeatherIcon, color, hoverColor } = useMemo<{ WeatherIcon: LucideIcon, color: string, hoverColor?: string }>(() => {
        if (onWeatherForecastExpanded) {
            return {
                WeatherIcon: ChevronDown,
                color: "text-gray-400",
                hoverColor: "text-gray-600"
            }
        }

        if (weather?.precipitation?.probability == null) {
            return {
                WeatherIcon: HelpCircle,
                color: "text-slate-400"
            }
        }

        if (weather?.precipitation?.probability > 40 && weather?.precipitation?.total >= 0.1) {
            return {
                WeatherIcon: CloudRain,
                color: "text-blue-500"
            }
        }

        if (weather?.clouds?.total > 70) {
            return {
                WeatherIcon: Cloud,
                color: "text-slate-500"
            }
        }

        if (weather?.clouds?.total > 20) {
            return {
                WeatherIcon: CloudSun,
                color: "text-amber-600"
            }
        }

        return {
            WeatherIcon: Sun,
            color: "text-amber-500"
        }
    }, [weather, onWeatherForecastExpanded])

    const confidenceColor = useMemo(() => {
        const confidence = weather?.clouds?.confidence;

        if (confidence === null) {
            return "text-slate-400"
        }

        if (confidence <= 50) {
            return "text-rose-500"
        }

        if (confidence <= 75) {
            return "text-amber-500"
        }

        return "text-emerald-500"
    }, [weather?.clouds?.confidence])

    return (
        <div className={`flex flex-col w-full ${weather.clouds ? "gap-1" : "gap-0"}`}>
            <div className="flex items-center gap-3 pr-1">
                <div className="flex items-center space-x-1 shrink-0">
                    {showTime && (
                        <div className="w-10 shrink-0 flex items-center text-gray-600">
                            <span className="text-[12px] font-bold tabular-nums">
                                {formatTimestamp(start, t("general.format.time"), timezone)}
                            </span>
                        </div>
                    )}
                    <div className={`mr-1 leading-none ${color} ${hoverColor && "hover:" + hoverColor}`}>
                        {onWeatherForecastExpanded ? (
                            <button
                                className="btn-icon-hover"
                                onClick={onWeatherForecastExpanded}>
                                <WeatherIcon size={14} />
                            </button>
                        ) : (
                            <WeatherIcon size={14} />
                        )}
                    </div>
                    <div className="text-[12px] text-slate-900 group relative hover:cursor-help">
                        <span className="min-w-[40px]">
                            {weather.temperature.toFixed(1)}°C
                        </span>
                        <Tooltip>
                            {weather?.clouds ? (
                                <>
                                    <Clock
                                        size={14}
                                        className="mr-1" />
                                    <span>
                                        {t("weather.expiration", { datetime: formatTimestamp(Math.max(weather.validity, getCurrentTimestamp()), t("general.format.time")) })}
                                    </span>
                                </>
                            ) : (
                                <>
                                    <Sun
                                        size={14}
                                        className="mr-1" />
                                    {t("weather.sun.altitude", { from: getSunAltitude(start, coordinates).toFixed(1), to: getSunAltitude(end, coordinates).toFixed(1) })}
                                </>
                            )}
                        </Tooltip>
                    </div>
                </div>
                {weather?.clouds ? (
                    <>
                        <div className="relative flex-1 group h-1.5 hover:cursor-help">
                            <div className="w-full h-full bg-slate-100 rounded-full flex overflow-hidden shadow-inner">
                                {weather.clouds?.low != null && (
                                    <div style={{ width: `${weather.clouds.low}%` }} className="bg-slate-600 h-full shrink-0" />
                                )}
                                {weather.clouds?.medium != null && (
                                    <div style={{ width: `${weather.clouds.medium}%` }} className="bg-slate-400 h-full shrink-0" />
                                )}
                                {weather.clouds?.high != null && (
                                    <div style={{ width: `${weather.clouds.high}%` }} className="bg-slate-300 h-full shrink-0" />
                                )}
                            </div>
                            <Tooltip>
                                <div className="flex flex-col gap-1 p-2 min-w-[120px]">
                                    <div className="flex justify-between gap-4 border-b border-white/20 pb-2 mb-1 text-white">
                                        <span>
                                            {t("weather.clouds.total")}
                                        </span>
                                        <b className="text-white">
                                            {weather.clouds.total.toFixed(0)}%
                                        </b>
                                    </div>
                                    {weather.clouds?.low != null && (
                                        <div className="flex justify-between gap-4 text-slate-300">
                                            <span>
                                                {t("weather.clouds.low")}
                                            </span>
                                            <b className="text-white">
                                                {weather.clouds.low.toFixed(0)}%
                                            </b>
                                        </div>
                                    )}
                                    {weather.clouds?.medium != null && (
                                        <div className="flex justify-between gap-4 text-slate-300">
                                            <span>
                                                {t("weather.clouds.medium")}
                                            </span>
                                            <b className="text-white">
                                                {weather.clouds.medium.toFixed(0)}%
                                            </b>
                                        </div>
                                    )}
                                    {weather.clouds?.high != null && (
                                        <div className="flex justify-between gap-4 text-slate-300">
                                            <span>
                                                {t("weather.clouds.high")}
                                            </span>
                                            <b className="text-white">
                                                {weather.clouds.high.toFixed(0)}%
                                            </b>
                                        </div>
                                    )}
                                </div>
                            </Tooltip>
                        </div>
                        {weather.clouds.confidence != null && (
                            <span className={`text-[10px] shrink-0 font-black ${confidenceColor}`}>
                                {weather.clouds.confidence.toFixed(0)}%
                            </span>
                        )}
                    </>
                ) : (
                    <div className="flex-1 flex items-center justify-end gap-2.5 text-[11px] font-medium pr-1">
                        {weather?.precipitation.total != null && (
                            <div className="flex items-center gap-1 text-blue-600 leading-none">
                                <CloudRain
                                    size={13}
                                    className="shrink-0" />
                                <span className="tabular-nums font-normal opacity-70 uppercase">
                                    {weather?.precipitation.total.toFixed(1)}mm
                                </span>
                            </div>
                        )}
                        {weather?.wind != null && (
                            <div className="flex items-center gap-1 text-slate-500 leading-none">
                                <Wind
                                    size={13}
                                    className="shrink-0" />
                                <span className="tabular-nums font-normal opacity-70 uppercase">
                                    {weather?.wind.toFixed(1)}m/s
                                </span>
                            </div>
                        )}
                    </div>
                )}
            </div>
            {weather?.clouds && (
                <div className="flex items-start justify-between text-[10px] space-x-0.5 mt-1 mb-1">
                    {weather.precipitation.probability != null && (
                        <div className="flex flex-col text-blue-500 items-center shrink-0">
                            <CloudRain size={14} />
                            <div className="flex flex-col items-center leading-none mt-1">
                                <span className="tabular-nums font-medium uppercase">
                                    {weather.precipitation.probability.toFixed(0)}%
                                </span>
                                <span className="text-[8px] tabular-nums uppercase">
                                    {weather?.precipitation.total.toFixed(1)}mm
                                </span>
                            </div>
                        </div>
                    )}
                    {weather.clouds?.total != null && (
                        <div className="flex flex-col items-center text-slate-700 shrink-0">
                            <Cloud size={14} />
                            <span className="tabular-nums font-medium uppercase">
                                {weather.clouds.total.toFixed(0)}%
                            </span>
                        </div>
                    )}
                    {weather.humidity != null && (
                        <div className="flex flex-col items-center text-cyan-700 shrink-0">
                            <Droplets size={14} />
                            <span className="tabular-nums font-medium uppercase">
                                {weather.humidity.toFixed(0)}%
                            </span>
                        </div>
                    )}
                    {weather.wind != null && (
                        <div className="flex flex-col items-center text-slate-500 shrink-0">
                            <Wind size={14} />
                            <span className="tabular-nums font-medium uppercase">
                                {weather.wind.toFixed(1)}m/s
                            </span>
                        </div>
                    )}
                    <div className="flex flex-col items-center text-cyan-400 shrink-0">
                        <div className="transition-transform duration-700 ease-in-out">
                            <ArrowUpLeftFromCircle
                                size={14}
                                strokeWidth={2.3}
                                style={{
                                    transform: `rotate(${45 + getSunAzimuth(start, coordinates)}deg)`,
                                    transformOrigin: "center center"
                                }}
                            />
                        </div>
                        <span className="tabular-nums font-medium uppercase">
                            {getSunAltitude(start, coordinates).toFixed(1)}°
                        </span>
                    </div>
                    <div className="flex flex-col items-center text-rose-400 shrink-0">
                        <div className="transition-transform duration-700 ease-in-out">
                            <ArrowUpLeftFromCircle
                                size={14}
                                strokeWidth={2.3}
                                style={{
                                    transform: `rotate(${45 + getSunAzimuth(end, coordinates)}deg)`,
                                    transformOrigin: "center center"
                                }}
                            />
                        </div>
                        <span className="tabular-nums font-medium uppercase">
                            {getSunAltitude(end, coordinates).toFixed(1)}°
                        </span>
                    </div>
                </div>
            )}
        </div>
    )
}