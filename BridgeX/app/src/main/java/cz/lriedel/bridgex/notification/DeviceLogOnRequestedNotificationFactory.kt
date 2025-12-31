package cz.lriedel.bridgex.notification

import android.content.Context
import android.content.Intent
import androidx.core.content.ContextCompat
import com.google.gson.Gson
import cz.lriedel.bridgex.device.DeviceForegroundService

class DeviceLogOnRequestedNotificationFactory(
    private val context: Context
) : NotificationFactory {
    private val gson = Gson()

    override suspend fun create(args: Map<String, Any>): Notification? {
        val serviceIntent = Intent(context, DeviceForegroundService::class.java).apply {
            val currentHeaders = NotificationContext.headers.get()
            if (currentHeaders != null) {
                putExtra("headers", gson.toJson(currentHeaders))
            }
        }

        ContextCompat.startForegroundService(context, serviceIntent)

        return null
    }
}
