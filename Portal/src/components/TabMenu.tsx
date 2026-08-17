import { useMemo, useEffect, useCallback } from "react"
import { useSearchParams } from "react-router-dom"
import type { TabMenuTab } from "../types/TabMenuTab"

interface TabMenuProps {
    tabs: TabMenuTab[]
    selectedTab?: string
    onTabSelected?: (tab: string) => void
}

export default function TabMenu({ tabs, selectedTab, onTabSelected }: TabMenuProps) {
    const setSelectedKey = useCallback((name: string) => {
        if (onTabSelected) {
            onTabSelected(name)
        }
    }, [onTabSelected])

    return tabs.filter(tab => tab.enabled).length > 1 && (
        <nav className="flex flex-wrap border-b border-gray-200">
            {tabs.map((tab, index) => tab.enabled && (
                <button
                    key={index}
                    onClick={() => setSelectedKey(tab.name)}
                    className={`flex-1 relative block whitespace-nowrap mt-2 py-4 px-6 font-medium text-center transition-colors duration-200
                        ${selectedTab === tab.name
                            ? "text-blue-700 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-full after:bg-blue-700"
                            : "text-gray-700 hover:text-blue-700 hover:after:absolute hover:after:left-0 hover:after:bottom-0 hover:after:h-0.5 hover:after:w-full hover:after:bg-blue-600"}`}>
                    {tab.label}
                </button>
            ))}
        </nav>
    )
}