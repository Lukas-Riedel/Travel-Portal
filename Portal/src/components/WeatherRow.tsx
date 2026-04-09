import { ArrowUpLeftFromCircle, Clock, Cloud, CloudRain, CloudSun, Droplets, HelpCircle, Sun as SunIcon, Wind, type LucideIcon } from "lucide-react"
import type { Sun, Weather } from "../types/CoreSwaggerTypes.ts"
import { useMemo } from "react"
import Tooltip from "./Tooltip.jsx"
import { useTranslation } from "react-i18next"
import { formatTimestamp } from "../utils/timeUtils.ts"

interface WeatherRowProps {
    weather: Weather | null
    sun: Sun | null
    className?: string
}

export default function WeatherRow({ weather, sun, className }: WeatherRowProps) {
    const { t } = useTranslation()

    const { WeatherIcon, color } = useMemo<{ WeatherIcon: LucideIcon, color: string }>(() => {
        if (weather?.precipitation?.probability === null) {
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
            WeatherIcon: SunIcon,
            color: "text-amber-500"
        }
    }, [weather])

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

    if (!weather) {
        // TODO: Loading placeholder?
        return null
    }
    
    return (
        <div className={`flex flex-col w-full ${weather.clouds ? "gap-1" : "gap-0"} ${className}`}>
            <div className="flex items-center gap-3 pr-1">
                <div className="flex items-center space-x-1 shrink-0">
                    <WeatherIcon className={`w-4 h-4 mr-1 leading-none shrink-0 ${color}`} />
                    <div className="text-[12px] text-slate-900 group relative cursor-help">
                        {weather.temperature}°C
                        {(weather.clouds?.low != null || weather.clouds?.medium != null || weather.clouds?.high != null) && (
                            <Tooltip>
                                <Clock
                                    size={14}
                                    className="mr-1" />
                                <span>
                                    {t("weather.updated", { datetime: formatTimestamp(weather.lastUpdate, t("general.format.time")) })}
                                </span>
                            </Tooltip>
                        )}
                    </div>
                </div>
                {(weather.clouds?.low != null || weather.clouds?.medium != null || weather.clouds?.high != null) ? (
                    <>
                        <div className="relative flex-1 group h-1.5">
                            <div className="w-full h-full bg-slate-100 rounded-full flex overflow-hidden shadow-inner cursor-help">
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
                                            {weather.clouds.total}%
                                        </b>
                                    </div>
                                    {weather.clouds?.low != null && (
                                        <div className="flex justify-between gap-4 text-slate-300">
                                            <span>
                                                {t("weather.clouds.low")}
                                            </span>
                                            <b className="text-white">
                                                {weather.clouds.low}%
                                            </b>
                                        </div>
                                    )}
                                    {weather.clouds?.medium != null && (
                                        <div className="flex justify-between gap-4 text-slate-300">
                                            <span>
                                                {t("weather.clouds.medium")}
                                            </span>
                                            <b className="text-white">
                                                {weather.clouds.medium}%
                                            </b>
                                        </div>
                                    )}
                                    {weather.clouds?.high != null && (
                                        <div className="flex justify-between gap-4 text-slate-300">
                                            <span>
                                                {t("weather.clouds.high")}
                                            </span>
                                            <b className="text-white">
                                                {weather.clouds.high}%
                                            </b>
                                        </div>
                                    )}
                                </div>
                            </Tooltip>
                        </div>
                        {weather.clouds.confidence != null && (
                            <span className={`text-[10px] shrink-0 font-black ${confidenceColor}`}>
                                {weather.clouds.confidence}%
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
                                    {weather?.precipitation.total}mm
                                </span>
                            </div>
                        )}
                        {weather?.wind != null && (
                            <div className="flex items-center gap-1 text-slate-500 leading-none">
                                <Wind
                                    size={13}
                                    className="shrink-0" />
                                <span className="tabular-nums font-normal opacity-70 uppercase">
                                    {weather?.wind}m/s
                                </span>
                            </div>
                        )}
                    </div>
                )}
            </div>
            {(weather.clouds?.low != null || weather.clouds?.medium != null || weather.clouds?.high != null) && (
                <div className="flex items-start justify-between text-[10px] space-x-0.5 mt-1 mb-1">
                    {weather.precipitation.probability != null && (
                        <div className="flex flex-col text-blue-500 items-center shrink-0 flex-l">
                            <CloudRain size={14} />
                            <div className="flex flex-col items-center leading-none mt-1">
                                <span className="tabular-nums font-medium uppercase">
                                    {weather.precipitation.probability}%
                                </span>
                                <span className="text-[8px] tabular-nums uppercase">
                                    {weather?.precipitation.total}mm
                                </span>
                            </div>
                        </div>
                    )}
                    {weather.clouds?.total != null && (
                        <div className="flex flex-col items-center text-slate-400 shrink-0 flex-l">
                            <Cloud size={14} />
                            <span className="tabular-nums font-medium uppercase">
                                {weather.clouds.total}%
                            </span>
                        </div>
                    )}
                    {weather.humidity != null && (
                        <div className="flex flex-col items-center text-cyan-700 shrink-0 flex-l">
                            <Droplets size={14} />
                            <span className="tabular-nums font-medium uppercase">
                                {weather.humidity}%
                            </span>
                        </div>
                    )}
                    {weather.wind != null && (
                        <div className="flex flex-col items-center text-slate-700 shrink-0 flex-l">
                            <Wind size={14} />
                            <span className="tabular-nums font-medium uppercase">
                                {weather.wind}m/s
                            </span>
                        </div>
                    )}
                    {sun?.altitude && sun?.azimuth && (
                        <>
                            <div className="flex flex-col items-center text-cyan-400 shrink-0 flex-l">
                                <div className="transition-transform duration-700 ease-in-out">
                                    <ArrowUpLeftFromCircle
                                        size={14}
                                        strokeWidth={2.3}
                                        style={{
                                            transform: `rotate(${45 + sun.azimuth.start}deg)`,
                                            transformOrigin: "center center"
                                        }}
                                    />
                                </div>
                                <span className="tabular-nums font-medium uppercase">
                                    {(sun.altitude.start).toFixed(1)}°
                                </span>
                            </div>
                            <div className="flex flex-col items-center text-rose-400 shrink-0 flex-l">
                                <div className="transition-transform duration-700 ease-in-out">
                                    <ArrowUpLeftFromCircle
                                        size={14}
                                        strokeWidth={2.3}
                                        style={{
                                            transform: `rotate(${45 + sun.azimuth.end}deg)`,
                                            transformOrigin: "center center"
                                        }}
                                    />
                                </div>
                                <span className="tabular-nums font-medium uppercase">
                                    {(sun.altitude.end).toFixed(1)}°
                                </span>
                            </div>
                        </>
                    )}
                </div>
            )}
        </div>
    )
}