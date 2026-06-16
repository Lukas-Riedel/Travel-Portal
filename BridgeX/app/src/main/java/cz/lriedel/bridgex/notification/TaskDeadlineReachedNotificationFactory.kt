package cz.lriedel.bridgex.notification

import android.content.Context
import cz.lriedel.bridgex.R

class TaskDeadlineReachedNotificationFactory(
    private val context: Context
) : NotificationFactory {
    override suspend fun create(args: Map<String, Any>): Notification? {
        val task = args["task"] as? String ?: return null

        return Notification(
            context.getString(R.string.title_task_deadline_reached),
            task,
            mapOf<String, Any>("task" to task)
        )
    }
}
