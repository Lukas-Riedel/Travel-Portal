import type { Label } from "../types/CoreSwaggerTypes.ts"
import { getUnicode } from "../utils/formattingUtils.ts"
import Bar from "./Bar.tsx"
import BarItem from "./BarItem.tsx"

interface LabelBarProps {
    labels: Label[] | null
}

export default function LabelBar({ labels }: LabelBarProps) {
    return labels && labels.length > 0 && (
        <Bar className="lg:flex-wrap">
            {labels.map(label => (
                <BarItem
                    key={label.id}
                    to={label}>
                    {label.metadata?.unicode && (
                        `${getUnicode(label.metadata.unicode)} `
                    )}
                    {label.name}
                </BarItem>
            ))}
        </Bar>
    )
}