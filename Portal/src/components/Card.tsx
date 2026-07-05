import React from "react"
import { cn } from "../utils/formattingUtils.ts"

interface CardProps {
    children: React.ReactNode
    className?: string
}

export default function Card({ children, className = "" }: CardProps) {
    return (
        <div className={cn("bg-white rounded-xl shadow-md p-3 w-full", className)}>
            {children}
        </div>
    )
}