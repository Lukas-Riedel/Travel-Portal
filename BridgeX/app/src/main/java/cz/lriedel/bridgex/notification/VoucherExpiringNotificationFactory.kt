package cz.lriedel.bridgex.notification

import android.content.Context
import cz.lriedel.bridgex.R
import java.time.Instant
import java.time.ZoneId
import java.time.format.DateTimeFormatter
import java.util.Locale

class VoucherExpiringNotificationFactory(
    private val context: Context
) : NotificationFactory {
    override suspend fun create(args: Map<String, Any>): Notification? {
        val issuer = args["issuer"] as? String ?: return null
        val value = args["value"] as? Double ?: return null
        val currency = args["currency"] as? String ?: return null
        val expiration = (args["expiration"] as? Double)?.toLong() ?: return null

        val formatter = DateTimeFormatter.ofPattern(context.getString(R.string.format_date))
            .withLocale(Locale.getDefault())
            .withZone(ZoneId.systemDefault())

        val formattedExpiration = formatter.format(Instant.ofEpochSecond(expiration))
        val valueFormatted = value.toBigDecimal().stripTrailingZeros().toPlainString()

        return Notification(
            context.getString(R.string.title_voucher_expiring),
            context.getString(R.string.message_voucher_expiring, issuer, valueFormatted, currency, formattedExpiration),
            mapOf("issuer" to issuer)
        )
    }
}
