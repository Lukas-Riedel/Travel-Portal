import { useCallback } from "react"
import { useSearchParams } from "react-router-dom"
import type { UseQueryParamStateResult } from "../types/UseQueryParamStateResult"
[]
export function useQueryParamState(paramName: string, defaultValue?: string): UseQueryParamStateResult {
    const [searchParams, setSearchParams] = useSearchParams()

    const value = searchParams.get(paramName) ?? defaultValue

    const setValue = useCallback(
        (newValue: string | null) => {
            const newSearchParams = new URLSearchParams(searchParams)
            if (newValue === null) {
                newSearchParams.delete(paramName)
            }
            else {
                newSearchParams.set(paramName, newValue)
            }
            setSearchParams(newSearchParams)
        },
        [paramName, searchParams, setSearchParams]
    )

    return [value, setValue]
}