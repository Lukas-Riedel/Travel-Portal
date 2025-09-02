package cz.lriedel.bridgex.notification

interface NotificationFactory {
    suspend fun create(args: Map<String, Any>): Notification?
}
