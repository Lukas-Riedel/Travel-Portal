import { Trash2, Wrench } from "lucide-react"

import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import type { Label, LabelMetadata } from "../types/CoreSwaggerTypes.ts"
import { getUnicode } from "../utils/formattingUtils.ts"
import AppLink from "./AppLink.tsx"
import Card from "./Card.tsx"
import LoadingCard from "./LoadingCard.tsx"

interface LabelCardProps {
    label: Label | null
    onLabelMetadataUpdated?: (metadata: LabelMetadata) => Promise<Label>
    onLabelRemoved?: () => Promise<void>
}

export default function LabelCard({ label, onLabelMetadataUpdated, onLabelRemoved }: LabelCardProps) {
    const { showUpdateLabelToast, showRemoveLabelToast } = usePredefinedUserInput()

    const handleLabelMetadataUpdated = () => {
        if (label && onLabelMetadataUpdated) {
            showUpdateLabelToast(label, onLabelMetadataUpdated)
        }
    }

    const handleLabelRemoved = () => {
        if (label && onLabelRemoved) {
            showRemoveLabelToast(onLabelRemoved)
        }
    }

    if (!label) {
        return (
            <LoadingCard />
        )
    }

    return (
        <Card className="text-center flex flex-col items-center justify-center">
            <div className="flex flex-col items-center justify-center flex-grow space-y-4">
                {label.metadata?.unicode && (
                    <div className="text-4xl">
                        {getUnicode(label.metadata.unicode)}
                    </div>
                )}
                <AppLink
                    to={label}
                    className="font-semibold text-lg hover:underline">
                    {label.name}
                </AppLink>
            </div>
            <div className="mt-4">
                {onLabelMetadataUpdated && (
                    <button
                        onClick={handleLabelMetadataUpdated}
                        className="rounded text-orange-600 hover:bg-gray-100 transition-colors p-2">
                        <Wrench size={16} />
                    </button>
                )}
                {onLabelRemoved && (
                    <button
                        onClick={handleLabelRemoved}
                        className="rounded text-red-600 hover:bg-gray-100 transition-colors p-2">
                        <Trash2 size={16} />
                    </button>
                )}
            </div>
        </Card>
    )
}
