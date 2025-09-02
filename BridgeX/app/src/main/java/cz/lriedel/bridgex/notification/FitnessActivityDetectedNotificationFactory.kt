package cz.lriedel.bridgex.notification

import android.content.Context
import android.content.Intent
import androidx.core.content.ContextCompat
import cz.lriedel.bridgex.fitness.FitnessForegroundService
import cz.lriedel.bridgex.fitness.FitnessInterval

class FitnessActivityDetectedNotificationFactory(
    private val context: Context
) : NotificationFactory {
    override suspend fun create(args: Map<String, Any>): Notification? {
        val intervalEntries = args["intervals"] as? List<*> ?: return null
        val fitnessIntervals = intervalEntries.mapNotNull { entry ->
            val interval = entry as? Map<*, *> ?: return@mapNotNull null
            val start = (interval["start"] as? Number)?.toLong() ?: return@mapNotNull null
            val end = (interval["end"] as? Number)?.toLong() ?: return@mapNotNull null
            FitnessInterval(start, end)
        }

        val serviceIntent = Intent(context, FitnessForegroundService::class.java).apply {
            putParcelableArrayListExtra("intervals", ArrayList(fitnessIntervals))
        }

        ContextCompat.startForegroundService(context, serviceIntent)

        return null
    }
}
