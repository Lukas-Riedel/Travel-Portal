package cz.lriedel.bridgex.notification

import android.content.Context
import cz.lriedel.bridgex.R

class NewDataConsistencyIssuesDetectedNotificationFactory(
    private val context: Context
) : NotificationFactory {
    override suspend fun create(args: Map<String, Any>): Notification? {
        val count = args["count"] as? Number ?: return null

        val convertedCount: Int = count.toInt() 
        return Notification(
            context.getString(R.string.title_issues_detected),
            context.getString(R.string.message_issues_detected, convertedCount),
            mapOf()
        )
    }
}
