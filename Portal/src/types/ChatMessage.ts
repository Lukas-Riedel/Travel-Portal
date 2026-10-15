import type { GenerativeContentPlaceholder } from "./CoreSwaggerTypes.ts"
import type { ChatMessageRole } from "./ChatMessageRole.ts"

export interface ChatMessage {
    id: string
    role: ChatMessageRole
    content: string
    placeholders?: Record<string, GenerativeContentPlaceholder>
    imageUrls?: string[]
}
