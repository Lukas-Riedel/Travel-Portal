import { useQuery } from "@tanstack/react-query"
import { useAuth } from "../contexts/AuthContext"
import { createDocument, listDocuments, removeDocument } from "../clients/coreClient"

export const useDocuments = () => {
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listDocuments"],
        queryFn: listDocuments,
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 12
    })

    const refetchDocuments = _ => query.refetch()

    return {
        // TODO: Map to Statistics objects
        documents: query.data,
        createDocument: (name, code, issuer, expiration) => createDocument(name, code, issuer, expiration).then(refetchDocuments),
        removeDocument: documentId => removeDocument(documentId).then(refetchDocuments)
    }
}