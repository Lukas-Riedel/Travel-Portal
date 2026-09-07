import type { ExpenseCurrency } from "./CoreSwaggerTypes.ts"

// TODO: Make this a part of the Core API model.
export interface AppConfiguration {
    dynamicLabels?: { name: string; interval: number }[]
    homeLocation?: {
        country: string
        latitude: number
        longitude: number
        timezone: string
        countryCode: string
    }
    expensify?: {
        mainCurrency: ExpenseCurrency
    }
    calendar?: {
        stays?: string
        trips?: string
        places?: string
        flights?: string
        watchedFlights?: string
    }
    timeTracking?: {
        currentFte: number
        expectedOvertimePerDay: number
        openingBalance: {
            tenure: number
            selfcare: number
            vacation: number
        }
    }
    generativeContentPrompt?: {
        placeExcerpt?: string
        alternativeAddress?: string
        tripHighlightsSelecting?: string
        yearHighlightsSelecting?: string
        placeHighlightsSelecting?: string
        categoryHighlightsSelecting?: string
    }
    openLineage?: {
        producer: {
            ibmCloud: { enabled: boolean }
            googleDrive: { enabled: boolean }
        }
    }
    highlight?: {
        attribute?: {
            sky?: { id: string; text?: string; value: number }[]
            shadows?: { id: string; text?: string; value: number }[]
            atmosphere?: { id: string; text?: string; value: number }[]
            impression?: { id: string; text?: string; value: number }[]
            composition?: { id: string; text?: string; value: number }[]
            circumstances?: { id: string; text?: string; value: number }[]
        }
        negativeTerms?: string[]
    }
    flightReminders?: { text: string; title: string; secondsBefore: number }[]
    agent?: Record<string, unknown>
    labels?: unknown
}
