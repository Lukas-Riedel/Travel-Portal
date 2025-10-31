import CardGrid from "./CardGrid"
import DocumentCard from "./DocumentCard"

export default function DocumentCardGrid({ documents, onDocumentRemoved }) {
    return (
        <CardGrid cardsPerRowCount={4}>
            {documents?.map(document => (
                <DocumentCard
                    key={document.id}
                    document={document}
                    onDocumentRemoved={onDocumentRemoved} />
            ))}
        </CardGrid>
    )
}
