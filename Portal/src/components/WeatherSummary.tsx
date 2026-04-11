import { useMemo, useState } from "react"
import type { Weather } from "../types/CoreSwaggerTypes.ts"
import WeatherRow from "./WeatherRow.tsx"
import type { Coordinates } from "../types/Coordinates.ts"
import { ChevronUp } from "lucide-react"
import { ONE_HOUR_SECONDS } from "../utils/timeUtils.ts"
import { useTranslation } from "react-i18next"

interface WeatherSummaryProps {
    weather: Weather[]
    coordinates: Coordinates
    start: number
    end: number
    timezone?: string
}

type WeatherAggregate = Weather & {
    counts: {
        temperature: number
        precipitationProbability: number
        precipitationTotal: number
        clouds: number
        cloudsConfidence: number
        wind: number
        humidity: number
    }
}

export default function WeatherSummary({ weather, coordinates, start, end, timezone }: WeatherSummaryProps) {
    const { t } = useTranslation()

    const [isExpanded, setIsExpanded] = useState(false)

    const weatherAggregate = useMemo(() => weather.reduce<WeatherAggregate>((acc, record) => ({
        temperature: acc.temperature + record.temperature,
        precipitation: {
            probability: record.precipitation.probability != null
                ? (acc.precipitation.probability === null ? record.precipitation.probability : Math.max(acc.precipitation.probability, record.precipitation.probability))
                : acc.precipitation.probability,
            total: record.precipitation.total != null ? acc.precipitation.total + record.precipitation.total : acc.precipitation.total
        },
        clouds: {
            total: record.clouds?.total != null ? acc.clouds.total + record.clouds?.total : acc.clouds.total,
            low: record.clouds?.low != null ? acc.clouds.low + record.clouds?.low : acc.clouds.low,
            medium: record.clouds?.medium != null ? acc.clouds.medium + record.clouds?.medium : acc.clouds.medium,
            high: record.clouds?.high != null ? acc.clouds.high + record.clouds?.high : acc.clouds.high,
            confidence: record.clouds?.confidence != null ? acc.clouds.confidence + record.clouds?.confidence : acc.clouds.confidence
        },
        wind: acc.wind + record.wind,
        humidity: record.humidity != null ? acc.humidity + record.humidity : acc.humidity,
        lastUpdate: record.lastUpdate < acc.lastUpdate ? record.lastUpdate : acc.lastUpdate,
        validity: record.validity < acc.validity ? record.validity : acc.validity,
        counts: {
            temperature: acc.counts.temperature + 1,
            precipitationProbability: record.precipitation.probability != null ? acc.counts.precipitationProbability + 1 : acc.counts.precipitationProbability,
            precipitationTotal: acc.counts.precipitationTotal + 1,
            clouds: record.clouds?.total != null ? acc.counts.clouds + 1 : acc.counts.clouds,
            cloudsConfidence: record.clouds?.confidence != null ? acc.counts.cloudsConfidence + 1 : acc.counts.cloudsConfidence,
            wind: acc.counts.wind + 1,
            humidity: record.humidity != null ? acc.counts.humidity + 1 : acc.counts.humidity
        }
    }), {
        temperature: 0,
        precipitation: {
            probability: null,
            total: 0
        },
        clouds: {
            total: 0,
            low: 0,
            medium: 0,
            high: 0,
            confidence: 0
        },
        wind: 0,
        humidity: 0,
        lastUpdate: Number.MAX_VALUE,
        validity: Number.MAX_VALUE,
        counts: {
            temperature: 0,
            precipitationProbability: 0,
            precipitationTotal: 0,
            clouds: 0,
            cloudsConfidence: 0,
            wind: 0,
            humidity: 0
        }
    }), [weather])

    const weatherSummary = useMemo<Weather>(() => ({
        temperature: weatherAggregate.counts.temperature > 0
            ? weatherAggregate.temperature / weatherAggregate.counts.temperature
            : null,
        precipitation: {
            probability: weatherAggregate.precipitation.probability,
            total: weatherAggregate.counts.precipitationTotal > 0
                ? weatherAggregate.precipitation.total
                : null
        },
        clouds: weatherAggregate.counts.clouds > 0 ? {
            total: Math.round(weatherAggregate.clouds.total / weatherAggregate.counts.clouds),
            low: Math.round(weatherAggregate.clouds.low / weatherAggregate.counts.clouds),
            medium: Math.round(weatherAggregate.clouds.medium / weatherAggregate.counts.clouds),
            high: Math.round(weatherAggregate.clouds.high / weatherAggregate.counts.clouds),
            confidence: weatherAggregate.counts.cloudsConfidence > 0
                ? Math.round(weatherAggregate.clouds.confidence / weatherAggregate.counts.cloudsConfidence)
                : null
        } : null,
        wind: weatherAggregate.counts.wind > 0
            ? weatherAggregate.wind / weatherAggregate.counts.wind
            : null,
        humidity: weatherAggregate.counts.humidity > 0
            ? Math.round(weatherAggregate.humidity / weatherAggregate.counts.humidity)
            : null,
        lastUpdate: weatherAggregate.lastUpdate === Number.MAX_VALUE ? null : weatherAggregate.lastUpdate,
        validity: weatherAggregate.validity === Number.MAX_VALUE ? null : weatherAggregate.validity
    }), [weatherAggregate])

    return isExpanded ? (
        <div className="pb-2">
            <div className="space-y-1">
                {weather.map((hourly, idx) => {
                    const effectiveStart = start - (start % ONE_HOUR_SECONDS) + idx * ONE_HOUR_SECONDS

                    return (
                        <WeatherRow
                            coordinates={coordinates}
                            weather={hourly}
                            start={effectiveStart}
                            end={effectiveStart + ONE_HOUR_SECONDS}
                            timezone={timezone}
                            showTime={true} />
                    )
                })}
            </div>
            <div className="flex justify-center">
                <button
                    className="btn-chip-gray flex justify-center w-full"
                    onClick={() => setIsExpanded(false)}>
                    <ChevronUp size={14} />
                </button>
            </div>
        </div >
    ) : (
        <WeatherRow
            coordinates={coordinates}
            weather={weatherSummary}
            start={start}
            end={end}
            onWeatherForecastExpanded={weather.length > 1 && (() => setIsExpanded(true))} />
    )
}