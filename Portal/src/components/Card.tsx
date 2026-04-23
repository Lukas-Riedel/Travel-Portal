import React from "react"
import { twMerge } from "tailwind-merge"
import { clsx, type ClassValue } from "clsx"

function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

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