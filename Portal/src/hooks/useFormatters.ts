import { useTranslation } from "react-i18next"
import { useCallback } from "react"
import { StatisticsUnit } from "../types/CoreSwaggerTypes.ts"
import type { UseFormattersResult } from "../types/UseFormattersResult.ts"
import { getCurrentTimestamp } from "../utils/timeUtils.ts"

type UnitFormatter = (value: number) => string

export function useFormatters(): UseFormattersResult {
    const { t } = useTranslation()

    const formatDuration = useCallback((value: number, includeSeconds?: boolean) => {
        const h = Math.floor(value / 3600)
        const m = Math.floor((value % 3600) / 60)
        const s = Math.round(value % 60)

        const parts = []
        if (h > 0) {
            parts.push(t("general.unit.hour", { count: h }))
        }

        if (m > 0) {
            parts.push(t("general.unit.minute", { count: m }))
        }

        if ((s > 0 || (h === 0 && m === 0)) && includeSeconds) {
            parts.push(t("general.unit.second", { count: s }))
        }

        return parts.join(" ") || t("general.unit.hour", { count: 0 })
    }, [t])

    const formatEvents = useCallback((v: number) => t("general.unit.event", { count: v }), [t])
    const formatKilometers = useCallback((v: number) => t("general.unit.kilometer", { count: Math.round(v) }), [t])
    const formatMillimeters = useCallback((v: number) => t("general.unit.millimeter", { count: Math.round(v) }), [t])
    const formatMeters = useCallback((v: number) => t("general.unit.meter", { count: Math.round(v) }), [t])
    const formatElevationMeters = useCallback((v: number) => t("general.unit.elevation", { count: Math.round(v) }), [t])
    const formatPhotos = useCallback((v: number) => t("general.unit.photo", { count: v }), [t])
    const formatNewProblems = useCallback((v: number) => t("general.unit.problem", { count: v }), [t])
    const formatCountries = useCallback((v: number) => t("general.unit.country", { count: v }), [t])
    const formatPlaces = useCallback((v: number) => t("general.unit.place", { count: v }), [t])
    const formatDays = useCallback((v: number) => t("general.unit.day", { count: v }), [t])
    const formatFlights = useCallback((v: number) => t("general.unit.flight", { count: v }), [t])
    const formatSteps = useCallback((v: number) => t("general.unit.step", { count: v }), [t])
    const formatVisits = useCallback((v: number) => t("general.unit.visit", { count: v }), [t])
    const formatAirports = useCallback((v: number) => t("general.unit.airport", { count: v }), [t])
    const formatNights = useCallback((v: number) => t("general.unit.night", { count: v }), [t])

    const formatLatitude = useCallback((value: number) => {
        const abs = Math.abs(value)
        const d = Math.floor(abs)
        const m = Math.floor((abs - d) * 60)
        const s = Math.round((abs - d - m / 60) * 3600)
        return `${d}° ${m}' ${s}" ${value >= 0 ? "N" : "S"}`
    }, [])

    const formatLongitude = useCallback((value: number) => {
        const abs = Math.abs(value)
        const d = Math.floor(abs)
        const m = Math.floor((abs - d) * 60)
        const s = Math.round((abs - d - m / 60) * 3600)
        return `${d}° ${m}' ${s}" ${value >= 0 ? "E" : "W"}`
    }, [])

    const formatTimeAgo = useCallback((timestamp: number) => {
        const seconds = Math.floor(getCurrentTimestamp() - timestamp)
        if (seconds < 60) {
            return t("general.time.ago.seconds")
        }

        const minutes = Math.floor(seconds / 60)
        if (minutes < 60) {
            return t("general.time.ago.minute", { count: minutes })
        }

        const hours = Math.floor(minutes / 60)
        if (hours < 24) {
            return t("general.time.ago.hour", { count: hours })
        }

        const days = Math.floor(hours / 24)
        return t("general.time.ago.day", { count: days })
    }, [t])

    const formatRefreshedBefore = useCallback((timestamp: number) => {
        const seconds = Math.floor(getCurrentTimestamp() - timestamp)
        if (seconds < 60) {
            return t("general.time.refreshed.ago.seconds")
        }

        const minutes = Math.floor(seconds / 60)
        if (minutes < 60) {
            return t("general.time.refreshed.ago.minute", { count: minutes })
        }

        const hours = Math.floor(minutes / 60)
        if (hours < 24) {
            return t("general.time.refreshed.ago.hour", { count: hours })
        }

        const days = Math.floor(hours / 24)
        return t("general.time.refreshed.ago.day", { count: days })
    }, [t])

    const formatStatisticsUnit = useCallback((unit: StatisticsUnit, value: number, mainCurrency?: string) => {
        const unitFormatters: Record<StatisticsUnit, UnitFormatter> = {
            [StatisticsUnit.Kilometers]: formatKilometers,
            [StatisticsUnit.ElevationMeters]: formatElevationMeters,
            [StatisticsUnit.Photos]: formatPhotos,
            [StatisticsUnit.Duration]: formatDuration,
            [StatisticsUnit.Countries]: formatCountries,
            [StatisticsUnit.Places]: formatPlaces,
            [StatisticsUnit.MainCurrency]: value => `${value} ${mainCurrency || ""}`.trim(),
            [StatisticsUnit.Days]: formatDays,
            [StatisticsUnit.Flights]: formatFlights,
            [StatisticsUnit.Steps]: formatSteps,
            [StatisticsUnit.BeforeDaysTimestamp]: formatTimeAgo,
            [StatisticsUnit.Visits]: formatVisits,
            [StatisticsUnit.Airports]: formatAirports,
            [StatisticsUnit.Nights]: formatNights,
            [StatisticsUnit.Latitude]: formatLatitude,
            [StatisticsUnit.Longitude]: formatLongitude
        }

        return unitFormatters[unit] ? unitFormatters[unit](value) : `${value} ${unit}`
    }, [t, formatKilometers, formatMeters, formatPhotos, formatDuration, formatCountries, formatPlaces, formatDays,
        formatFlights, formatSteps, formatVisits, formatAirports, formatNights, formatLatitude, formatLongitude])

    return {
        formatDuration,
        formatEvents,
        formatMillimeters,
        formatKilometers,
        formatMeters,
        formatElevationMeters,
        formatPhotos,
        formatNewProblems,
        formatCountries,
        formatPlaces,
        formatDays,
        formatFlights,
        formatSteps,
        formatVisits,
        formatAirports,
        formatNights,
        formatLatitude,
        formatLongitude,
        formatTimeAgo,
        formatRefreshedBefore,
        formatStatisticsUnit
    }
}