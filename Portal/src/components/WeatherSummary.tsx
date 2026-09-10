import { ChevronUp } from "lucide-react"
import { useState } from "react"
import { useTranslation } from "react-i18next"

import type { Coordinates } from "../types/Coordinates.ts"
import type { Weather } from "../types/CoreSwaggerTypes.ts"
import { ONE_HOUR_SECONDS } from "../utils/timeUtils.ts"
import WeatherRow from "./WeatherRow.tsx"

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

    const weatherAggregate = weather.reduce<WeatherAggregate>((acc, record) => ({
        temperature: acc.temperature + record.temperature,
        precipitation: {
            probability: record.precipitation.probability !== undefined
                ? (acc.precipitation.probability === undefined ? record.precipitation.probability : Math.max(acc.precipitation.probability ?? 0, record.precipitation.probability))
                : acc.precipitation.probability,
            total: record.precipitation.total !== undefined ? acc.precipitation.total + record.precipitation.total : acc.precipitation.total
        },
        clouds: {
            total: record.clouds?.total !== undefined ? (acc.clouds?.total ?? 0) + record.clouds?.total : (acc.clouds?.total ?? 0),
            low: record.clouds?.low !== undefined ? (acc.clouds?.low ?? 0) + record.clouds?.low : (acc.clouds?.low ?? 0),
            medium: record.clouds?.medium !== undefined ? (acc.clouds?.medium ?? 0) + record.clouds?.medium : (acc.clouds?.medium ?? 0),
            high: record.clouds?.high !== undefined ? (acc.clouds?.high ?? 0) + record.clouds?.high : (acc.clouds?.high ?? 0),
            confidence: record.clouds?.confidence !== undefined ? (acc.clouds?.confidence ?? 0) + record.clouds?.confidence : (acc.clouds?.confidence ?? 0)
        },
        wind: acc.wind + record.wind,
        humidity: record.humidity !== undefined ? (acc.humidity ?? 0) + record.humidity : (acc.humidity ?? 0),
        lastUpdate: record.lastUpdate < acc.lastUpdate ? record.lastUpdate : acc.lastUpdate,
        validity: record.validity < acc.validity ? record.validity : acc.validity,
        counts: {
            temperature: acc.counts.temperature + 1,
            precipitationProbability: record.precipitation.probability !== undefined ? acc.counts.precipitationProbability + 1 : acc.counts.precipitationProbability,
            precipitationTotal: acc.counts.precipitationTotal + 1,
            clouds: record.clouds?.total !== undefined ? acc.counts.clouds + 1 : acc.counts.clouds,
            cloudsConfidence: record.clouds?.confidence !== undefined ? acc.counts.cloudsConfidence + 1 : acc.counts.cloudsConfidence,
            wind: acc.counts.wind + 1,
            humidity: record.humidity !== undefined ? acc.counts.humidity + 1 : acc.counts.humidity
        }
    }), {
        temperature: 0,
        precipitation: {
            probability: undefined,
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
    })

    const weatherSummary = {
        temperature: weatherAggregate.counts.temperature > 0
            ? weatherAggregate.temperature / weatherAggregate.counts.temperature
            : 0,
        precipitation: {
            probability: weatherAggregate.precipitation.probability,
            total: weatherAggregate.precipitation.total
        },
        clouds: weatherAggregate.clouds && weatherAggregate.counts.clouds > 0 ? {
            total: Math.round(weatherAggregate.clouds.total / weatherAggregate.counts.clouds),
            low: weatherAggregate.clouds.low && Math.round(weatherAggregate.clouds.low / weatherAggregate.counts.clouds),
            medium: weatherAggregate.clouds.medium && Math.round(weatherAggregate.clouds.medium / weatherAggregate.counts.clouds),
            high: weatherAggregate.clouds.high && Math.round(weatherAggregate.clouds.high / weatherAggregate.counts.clouds),
            confidence: weatherAggregate.clouds.confidence && weatherAggregate.counts.cloudsConfidence > 0
                ? Math.round(weatherAggregate.clouds.confidence / weatherAggregate.counts.cloudsConfidence)
                : undefined
        } : undefined,
        wind: weatherAggregate.counts.wind > 0
            ? weatherAggregate.wind / weatherAggregate.counts.wind
            : 0,
        humidity: weatherAggregate.humidity && weatherAggregate.counts.humidity > 0
            ? Math.round(weatherAggregate.humidity / weatherAggregate.counts.humidity)
            : undefined,
        lastUpdate: weatherAggregate.lastUpdate === Number.MAX_VALUE ? 0 : weatherAggregate.lastUpdate,
        validity: weatherAggregate.validity === Number.MAX_VALUE ? 0 : weatherAggregate.validity
    } satisfies Weather

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
                    className="btn-chip flex justify-center w-full"
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
            onWeatherForecastExpanded={weather.length > 1 ? () => setIsExpanded(true) : undefined} />
    )
}