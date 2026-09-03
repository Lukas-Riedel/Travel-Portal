import type { Document } from "../types/CoreSwaggerTypes.ts"
import CardGrid from "./CardGrid.tsx"
import DocumentCard from "./DocumentCard.tsx"

interface DocumentCardGridProps {
    documents: Document[] | null
    rowSize: number
    columnSize?: number
    onDocumentRemoved?: (documentId: string) => Promise<void>
}

export default function DocumentCardGrid({ documents, rowSize, columnSize, onDocumentRemoved }: DocumentCardGridProps) {
    return (
        <CardGrid
            rowSize={rowSize}
            columnSize={columnSize}>
            {documents?.map(document => (
                <DocumentCard
                    key={document.id}
                    document={document}
                    onDocumentRemoved={onDocumentRemoved} />
            ))}
        </CardGrid>
    )
}
