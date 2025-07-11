import { useCallback, useState } from "react"
import { useNotifications } from "../contexts/NotificationContext"

export const useEvents = eventName => {
    const { messages } = useNotifications()

    const [readMessageIds, setReadMessageIds] = useState(() => new Set())

    const markAsRead = useCallback(messageId => {
        setReadMessageIds(prev => new Set(prev).add(messageId))
    }, [])

    return messages
        ?.filter(msg => msg.data?.event === eventName && !readMessageIds.has(msg.messageId))
        ?.map(msg => ({ ...msg.data?.args, markAsRead: () => markAsRead(msg.messageId) })) ?? []
}