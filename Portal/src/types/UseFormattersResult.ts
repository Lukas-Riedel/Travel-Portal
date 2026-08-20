import type { StatisticsUnit } from "./CoreSwaggerTypes.ts"

export interface UseFormattersResult {
    formatDuration: (value: number, includeSeconds?: boolean) => string
    formatEvents: (value: number) => string
    formatKilometers: (value: number) => string
    formatMillimeters: (value: number) => string
    formatMeters: (value: number) => string
    formatElevationMeters: (value: number) => string
    formatPhotos: (value: number) => string
    formatNewProblems: (value: number) => string
    formatCountries: (value: number) => string
    formatPlaces: (value: number) => string
    formatDays: (value: number) => string
    formatFlights: (value: number) => string
    formatSteps: (value: number) => string
    formatVisits: (value: number) => string
    formatAirports: (value: number) => string
    formatNights: (value: number) => string
    formatLatitude: (value: number) => string
    formatLongitude: (value: number) => string
    formatTimeAgo: (timestamp: number) => string
    formatRefreshedBefore: (timestamp: number) => string
    formatStatisticsUnit: (unit: StatisticsUnit, value: number, mainCurrency?: string) => string
}