import { createDocument, listDocuments, removeDocument } from "../clients/coreClient.ts"
import type { UseDocumentsResult } from "../types/UseDocumentsResult.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

export const useDocuments = (): UseDocumentsResult => {
    const { response, refetchResponse } = useQuery({
        queryKey: ["listDocuments"],
        queryFn: listDocuments,
        staleTime: ONE_DAY_SECONDS * 1000
    })

    return {
        documents: response,
        createDocument: (name: string, code: string, issuer: string, expiration?: number) => createDocument(name, code, issuer, expiration).then(refetchResponse),
        removeDocument: (documentId: string) => removeDocument(documentId).then(refetchResponse)
    }
}