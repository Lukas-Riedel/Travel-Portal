package cz.lriedel.bridgex.notification

import android.content.Context

class FlightReminderReceivedNotificationFactory(
    private val context: Context
) : NotificationFactory {
    override suspend fun create(args: Map<String, Any>): Notification? {
        val flight = args["flight"] as? String ?: return null
        val title = args["title"] as? String ?: return null
        val text = args["text"] as? String ?: return null

        return Notification(
            title,
            text,
            mapOf<String, Any>("flight" to flight)
        )
    }
}
