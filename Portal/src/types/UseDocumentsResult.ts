import type { Document } from "./CoreSwaggerTypes.ts"

export interface UseDocumentsResult {
    documents?: Document[]
    createDocument: (name: string, code: string, issuer: string, expiration?: number) => Promise<Document>
    removeDocument: (documentId: string) => Promise<void>
}