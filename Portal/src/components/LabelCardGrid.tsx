import type { Label, LabelMetadata } from "../types/CoreSwaggerTypes.ts"
import CardGrid from "./CardGrid.tsx"
import LabelCard from "./LabelCard.tsx"

interface LabelCardGridProps {
    labels: Label[] | null
    rowSize: number
    columnSize?: number
    onLabelMetadataUpdated?: (labelId: string, metadata: LabelMetadata) => Promise<Label>
    onLabelRemoved?: (labelId: string) => Promise<void>
}

export default function LabelCardGrid({ labels, rowSize, columnSize, onLabelMetadataUpdated, onLabelRemoved }: LabelCardGridProps) {
    return (
        <CardGrid
            rowSize={rowSize}
            columnSize={columnSize}>
            {labels?.map(label => (
                <LabelCard
                    key={label.id}
                    label={label}
                    onLabelMetadataUpdated={onLabelMetadataUpdated ? metadata => onLabelMetadataUpdated(label.id, metadata) : undefined}
                    onLabelRemoved={onLabelRemoved ? () => onLabelRemoved(label.id) : undefined} />
            ))}
        </CardGrid>
    )
}
