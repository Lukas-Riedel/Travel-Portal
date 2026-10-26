import type { LucideIcon } from "lucide-react"

import { FactCardAccent } from "../types/FactCardAccent.ts"
import Card from "./Card.tsx"
import LoadingCard from "./LoadingCard.tsx"

interface FactCardProps {
    value: string | null
    icon: LucideIcon
    label: string
    description?: string
    accent?: FactCardAccent
}

export default function FactCard({ icon: Icon, label, value, description, accent = FactCardAccent.Neutral }: FactCardProps) {
    if (!value) {
        return (
            <LoadingCard />
        )
    }

    return (
        <Card className="p-4 flex items-start gap-3">
            <div className="text-gray-400 mt-0.5 shrink-0">
                <Icon size={18} />
            </div>
            <div>
                <div className="text-xs text-gray-400 uppercase tracking-wide mb-1">
                    {label}
                </div>
                <div className={`text-xl font-semibold ${accent === FactCardAccent.Positive ? "text-green-600" : accent === FactCardAccent.Negative ? "text-red-500" : "text-gray-800"}`}>
                    {value}
                </div>
                {description && (
                    <div className="text-xs text-gray-400 mt-0.5">
                        {description}
                    </div>
                )}
            </div>
        </Card>
    )
}
