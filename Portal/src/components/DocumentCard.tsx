import { useMemo } from "react"
import { Trash2 } from "lucide-react"
import { useTranslation } from "react-i18next"
import LoadingCard from "./LoadingCard.tsx"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import type { Document } from "../types/CoreSwaggerTypes.ts"
import Card from "./Card.tsx"
import PropertyCardContent from "./PropertyCardContent.tsx"
import { formatTimestamp } from "../utils/timeUtils.ts"

interface DocumentCardProps {
    document: Document | null
    onDocumentRemoved?: (documentId: string) => Promise<void>
}

export default function DocumentCard({ document, onDocumentRemoved }: DocumentCardProps) {
    const { t } = useTranslation()
    const { showRemoveDocumentToast } = usePredefinedUserInput()

    const handleDelete = () => {
        if (onDocumentRemoved) {
            showRemoveDocumentToast(() => onDocumentRemoved(document.id))
        }
    }

    const properties = useMemo(() => document && ({
        [t("document.label.code")]: document.code,
        [t("document.label.issuer")]: document.issuer,
        [t("document.label.expiration")]: document.expiration && formatTimestamp(document.expiration, t("general.format.date.year.included"))
    }), [document, t])

    if (!document) {
        return (
            <LoadingCard />
        )
    }

    return (
        <Card>
            <div className="flex justify-start items-center">
                <span className="text-lg font-semibold">
                    {document.name}
                </span>
                {onDocumentRemoved && (
                    <button
                        onClick={handleDelete}
                        className="p-1 rounded text-red-800 hover:bg-gray-100 transition-colors ml-auto">
                        <Trash2 size={16} />
                    </button>
                )}
            </div>
            <PropertyCardContent properties={properties} />
        </Card>
    )
}