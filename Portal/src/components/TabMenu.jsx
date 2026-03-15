import { useMemo, useEffect, useCallback } from "react"
import { useSearchParams } from "react-router-dom"

const tabUrlQueryParamName = "tab"

export default function TabMenu({ labels, onActiveTabChanged }) {
    const [searchParams, setSearchParams] = useSearchParams()

    const labelNames = useMemo(() => labels.map(label => label.tab), [labels])

    const activeTabName = useMemo(() => {
        const tabNameFromUrl = searchParams.get(tabUrlQueryParamName)

        if (tabNameFromUrl && labelNames.includes(tabNameFromUrl)) {
            return tabNameFromUrl
        }
        return labelNames[0]
    }, [labelNames, searchParams])

    const activeTabIndex = useMemo(() => labelNames.indexOf(activeTabName), [labelNames, activeTabName])

    const setActiveTab = useCallback(index => {
        const newSearchParams = new URLSearchParams(searchParams)
        const newTabName = labelNames[index]

        if (index === 0) {
            newSearchParams.delete(tabUrlQueryParamName)
        }
        else {
            newSearchParams.set(tabUrlQueryParamName, newTabName)
        }

        setSearchParams(newSearchParams)
    }, [labelNames, searchParams, setSearchParams])

    useEffect(() => {
        if (onActiveTabChanged) {
            onActiveTabChanged(activeTabIndex)
        }
    }, [activeTabIndex, onActiveTabChanged])


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