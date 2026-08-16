import { useMemo, useEffect, useCallback } from "react"
import { useSearchParams } from "react-router-dom"
import type { TabMenuLabel } from "../types/TabMenuLabel"

const TAB_URL_QUERY_PARAM_NAME = "tab"

interface TabMenuProps {
    labels: TabMenuLabel[]
    onActiveTabChanged?: (index: number) => void
}

export default function TabMenu({ labels, onActiveTabChanged }: TabMenuProps) {
    const [searchParams, setSearchParams] = useSearchParams()

    const tabNames = useMemo(() => labels.map(label => label.tab), [labels])

    const activeTabName = useMemo(() => {
        const tabNameFromUrl = searchParams.get(TAB_URL_QUERY_PARAM_NAME)
        if (tabNameFromUrl && tabNames.includes(tabNameFromUrl)) {
            return tabNameFromUrl
        }

        return tabNames[0]
    }, [tabNames, searchParams])

    // TODO: Extract URL search params logic into a new hook, and make TabMenu a pure controlled component.
    const setActiveTab = useCallback((index: number) => {
        const newSearchParams = new URLSearchParams(searchParams)
        const newTabName = tabNames[index]

        if (index === 0) {
            newSearchParams.delete(TAB_URL_QUERY_PARAM_NAME)
        }
        else {
            newSearchParams.set(TAB_URL_QUERY_PARAM_NAME, newTabName)
        }

        setSearchParams(newSearchParams)
    }, [tabNames, searchParams, setSearchParams])

    useEffect(() => {
        if (onActiveTabChanged) {
            onActiveTabChanged(tabNames.indexOf(activeTabName))
        }
    }, [tabNames, activeTabName, onActiveTabChanged])

    return labels.filter(label => label.enabled).length > 1 && (
        <nav className="flex flex-wrap border-b border-gray-200">
            {labels.map((label, index) => label.enabled && (
                <button
                    key={index}
                    onClick={() => setActiveTab(index)}
                    className={`flex-1 relative block whitespace-nowrap mt-2 py-4 px-6 font-medium text-center transition-colors duration-200
                        ${activeTabName === label.tab
                            ? "text-blue-700 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-full after:bg-blue-700"
                            : "text-gray-700 hover:text-blue-700 hover:after:absolute hover:after:left-0 hover:after:bottom-0 hover:after:h-0.5 hover:after:w-full hover:after:bg-blue-600"}`}>
                    {label.name}
                </button>
            ))}
        </nav>
    )
}