package cz.lriedel.bridgex.notification

import android.content.Context
import cz.lriedel.bridgex.R
import java.time.Instant
import java.time.ZoneId
import java.time.format.DateTimeFormatter
import java.util.Locale

class SubscriptionExpiringNotificationFactory(
    private val context: Context
) : NotificationFactory {
    override suspend fun create(args: Map<String, Any>): Notification? {
        val description = args["description"] as? String ?: return null
        val expiration = (args["expiration"] as? Double)?.toLong() ?: return null

        val formatter = DateTimeFormatter.ofPattern(context.getString(R.string.format_date))
            .withLocale(Locale.getDefault())
            .withZone(ZoneId.systemDefault())

        val formattedExpiration = formatter.format(Instant.ofEpochSecond(expiration))

        return Notification(
            context.getString(R.string.title_subscription_expiring),
            context.getString(R.string.message_subscription_expiring, description, formattedExpiration),
            emptyMap()
        )
    }
}
