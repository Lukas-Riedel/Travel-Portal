export interface Event {
    name: string
    args: Record<string, any>
    markAsRead: () => void
}