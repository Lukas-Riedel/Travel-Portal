import React from "react"
import { cn } from "../utils/formattingUtils.ts"
import type { Navigable } from "../types/Navigable.ts"
import AppLink from "./AppLink.tsx"

interface BarItemProps {
    to?: Navigable
    children: React.ReactNode
    className?: string
}

export default function BarItem({ to, children, className = "justify-center text-center text-sm font-medium" }: BarItemProps) {
    const effectiveClassName = cn("flex w-full lg:w-auto items-center px-4 py-2 bg-white rounded-lg shadow hover:bg-gray-100 transition", className)
    
    return to ? (
        <AppLink
            to={to}
            className={effectiveClassName}>
            {children}
        </AppLink>
    ) : (
        <div className={effectiveClassName}>
            {children}
        </div>
    )
}