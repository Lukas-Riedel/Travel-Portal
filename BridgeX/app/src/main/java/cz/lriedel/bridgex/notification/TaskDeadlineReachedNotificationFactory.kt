package cz.lriedel.bridgex.notification

import android.content.Context
import cz.lriedel.bridgex.R

class TaskDeadlineReachedNotificationFactory(
    private val context: Context
) : NotificationFactory {
    override suspend fun create(args: Map<String, Any>): Notification? {
        val title = args["title"] as? String ?: context.getString(R.string.title_task_deadline_reached)
        val task = args["task"] as? String ?: return null
        val url = args["url"] as? String
        val tripId = args["tripId"] as? String

        val intentExtras = mutableMapOf<String, Any>("task" to task)
        if (url != null) {
            intentExtras["url"] = url
        }
        if (tripId != null) {
            intentExtras["tripId"] = tripId
        }

        return Notification(title, task, intentExtras)
    }
}
