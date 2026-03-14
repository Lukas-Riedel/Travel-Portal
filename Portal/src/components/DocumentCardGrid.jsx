import CardGrid from "./CardGrid.tsx"
import DocumentCard from "./DocumentCard"

export default function DocumentCardGrid({ documents, onDocumentRemoved }) {
    return (
        <CardGrid rowSize={4}>
            {documents?.map(document => (
                <DocumentCard
                    key={document.id}
                    document={document}
                    onDocumentRemoved={onDocumentRemoved} />
            ))}
        </CardGrid>
    )
}
