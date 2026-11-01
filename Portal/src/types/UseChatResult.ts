import type { ChatMessage } from "./ChatMessage.ts"

export interface UseChatResult {
    messages: ChatMessage[]
    isLoading: boolean
    sendMessage: (text: string) => Promise<string | undefined>
    clearConversation: () => void
}
