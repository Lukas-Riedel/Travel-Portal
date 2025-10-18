package cz.lriedel.bridgex.notification

import android.content.Context
import cz.lriedel.bridgex.R
import java.util.Locale
import java.time.Instant
import java.time.ZoneId
import java.time.format.DateTimeFormatter

class FlightLoggedNotificationFactory(
    private val context: Context
) : NotificationFactory {
    override suspend fun create(args: Map<String, Any>): Notification? {
        val flight = args["flight"] as? String ?: return null
        val to = args["to"] as? String ?: return null
        val actualArrival = (args["actualArrival"] as? Number)?.toLong() ?: return null
        val timezone = args["timezone"] as? String ?: return null

        val formatter = DateTimeFormatter.ofPattern("HH:mm")
            .withLocale(Locale.getDefault())
            .withZone(ZoneId.of(timezone))

        val formattedActualArrival = formatter.format(Instant.ofEpochSecond(actualArrival))

        return Notification(
            context.getString(R.string.title_flight_landed),
            context.getString(R.string.message_flight_landed, flight, to, formattedActualArrival),
            mapOf()
        )
    }
}
