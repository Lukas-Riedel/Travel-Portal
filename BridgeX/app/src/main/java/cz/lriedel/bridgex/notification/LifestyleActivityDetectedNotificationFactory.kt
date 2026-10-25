package cz.lriedel.bridgex.notification

import android.content.Context
import android.content.Intent
import androidx.core.content.ContextCompat
import com.google.gson.Gson
import cz.lriedel.bridgex.lifestyle.LifestyleForegroundService
import cz.lriedel.bridgex.lifestyle.LifestyleInterval

class LifestyleActivityDetectedNotificationFactory(
    private val context: Context
) : NotificationFactory {
    private val gson = Gson()

    override suspend fun create(args: Map<String, Any>): Notification? {
        val intervalEntries = args["intervals"] as? List<*> ?: return null
        val height = (args["height"] as? Number)?.toLong() ?: return null
        val birthYear = (args["birthYear"] as? Number)?.toLong() ?: return null
        val male = args["male"] as? Boolean ?: return null
        val lifestyleIntervals = intervalEntries.mapNotNull { entry ->
            val interval = entry as? Map<*, *> ?: return@mapNotNull null
            val start = (interval["start"] as? Number)?.toLong() ?: return@mapNotNull null
            val end = (interval["end"] as? Number)?.toLong() ?: return@mapNotNull null
            LifestyleInterval(start, end, height, birthYear, male)
        }

        val serviceIntent = Intent(context, LifestyleForegroundService::class.java).apply {
            putParcelableArrayListExtra("intervals", ArrayList(lifestyleIntervals))
            val currentHeaders = NotificationContext.headers.get()
            if (currentHeaders != null) {
                putExtra("headers", gson.toJson(currentHeaders))
            }
        }

        ContextCompat.startForegroundService(context, serviceIntent)

        return null
    }
}
