import { useSearchParams } from "react-router-dom"

import type { UseQueryParamStateResult } from "../types/UseQueryParamStateResult"

export function useQueryParamState(paramName: string, defaultValue?: string): UseQueryParamStateResult {
    const [searchParams, setSearchParams] = useSearchParams()

    const value = searchParams.get(paramName) ?? defaultValue ?? null

    const setValue = (newValue: string | null) => {
        const newSearchParams = new URLSearchParams(searchParams)
        if (newValue === null) {
            newSearchParams.delete(paramName)
        }
        else {
            newSearchParams.set(paramName, newValue)
        }
        setSearchParams(newSearchParams)
    }

    return [value, setValue]
}