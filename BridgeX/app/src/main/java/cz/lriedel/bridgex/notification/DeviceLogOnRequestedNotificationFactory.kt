package cz.lriedel.bridgex.notification

import android.content.Intent
import androidx.core.content.ContextCompat
import android.content.Context
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
