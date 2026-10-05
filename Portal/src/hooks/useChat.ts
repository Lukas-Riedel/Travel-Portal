import { useRef, useState } from "react"
import { v4 as uuidv4 } from "uuid"

import { createGenerativeContent } from "../clients/coreClient.ts"
import type { ChatMessage } from "../types/ChatMessage.ts"
import { ChatMessageRole } from "../types/ChatMessageRole.ts"
import type { UseChatResult } from "../types/UseChatResult.ts"

export const useChat = (): UseChatResult => {
    const [messages, setMessages] = useState<ChatMessage[]>([])
    const [isLoading, setIsLoading] = useState(false)
    const conversationId = useRef<string | undefined>(undefined)

    const sendMessage = async (text: string): Promise<string | undefined> => {
        const trimmed = text.trim()
        if (!trimmed || isLoading) {
            return undefined
        }

        const userMessage: ChatMessage = {
            id: uuidv4(),
            role: ChatMessageRole.User,
            content: trimmed
        }

        setMessages(prev => [...prev, userMessage])
        setIsLoading(true)

        const result = await createGenerativeContent(trimmed, conversationId.current)
        if (!result.content) {
            setMessages(prev => prev.filter(m => m.id !== userMessage.id).concat({
                id: uuidv4(),
                role: ChatMessageRole.Error,
                content: trimmed
            }))

            return trimmed
        }

        conversationId.current = result.conversationId

        setMessages(prev => [...prev, {
            id: uuidv4(),
            role: ChatMessageRole.Assistant,
            content: result.content
        }])

        setIsLoading(false)
        return undefined
    }

    const clearConversation = () => {
        setMessages([])
        conversationId.current = undefined
    }

    return {
        messages,
        isLoading,
        sendMessage,
        clearConversation
    }
}
