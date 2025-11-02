package cz.lriedel.bridgex.notification

import android.content.Context
import android.content.Intent
import androidx.core.content.ContextCompat
import cz.lriedel.bridgex.device.DeviceForegroundService

class DeviceLogOnRequestedNotificationFactory(
    private val context: Context
) : NotificationFactory {

    override suspend fun create(args: Map<String, Any>): Notification? {
        val serviceIntent = Intent(context, DeviceForegroundService::class.java)

        ContextCompat.startForegroundService(context, serviceIntent)

        return null
    }
}
