package cz.lriedel.bridgex.notification

import android.content.Context
import cz.lriedel.bridgex.R

class DayItinerarySharedNotificationFactory(
    private val context: Context
) : NotificationFactory {
    override suspend fun create(args: Map<String, Any>): Notification? {
        val text = args["text"] as? String ?: return null
        val tripId = args["tripId"] as? String ?: return null

        return Notification(
            context.getString(R.string.title_itinerary_shared),
            text,
            mapOf<String, Any>("tripId" to tripId)
        )
    }
}
