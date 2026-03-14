import React from "react"

interface CardProps {
    children: React.ReactNode
    className?: string
}

export default function Card({ children, className = "" }: CardProps) {
    return (
        <div className={`bg-white rounded-xl shadow-md p-4 w-full flex flex-col items-center justify-center ${className}`}>
            {children}
        </div>
    )
}