import type { ChatMessageRole } from "./ChatMessageRole.ts"

export interface ChatMessage {
    id: string
    role: ChatMessageRole
    content: string
}
