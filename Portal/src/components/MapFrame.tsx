import type { ReactNode } from "react"

interface MapFrameProps {
    children: ReactNode
}

export default function MapFrame({ children }: MapFrameProps) {
    return (
        <div className="h-[400px] md:h-[700px] my-4">
            {children}
        </div>
    )
}
