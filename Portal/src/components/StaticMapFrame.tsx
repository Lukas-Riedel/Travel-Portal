import type { ReactNode } from "react"

interface StaticMapFrameProps {
    children: ReactNode
}

export default function StaticMapFrame({ children }: StaticMapFrameProps) {
    return (
        <div className="h-[400px] md:h-[700px] my-4">
            {children}
        </div>
    )
}
