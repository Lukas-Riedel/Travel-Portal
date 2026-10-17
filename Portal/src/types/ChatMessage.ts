import type { ChatMessageRole } from "./ChatMessageRole.ts"
import type { Highlight } from "./CoreSwaggerTypes.ts"

export interface ChatMessage {
    id: string
    role: ChatMessageRole
    content: string
    highlights?: Highlight[]
}
