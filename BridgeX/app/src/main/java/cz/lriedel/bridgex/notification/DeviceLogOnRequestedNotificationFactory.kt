package cz.lriedel.bridgex.notification

import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.os.BatteryManager
import androidx.core.content.ContextCompat
import com.google.gson.Gson
import cz.lriedel.bridgex.device.DeviceForegroundService

class DeviceLogOnRequestedNotificationFactory(
    private val context: Context
) : NotificationFactory {
    private val gson = Gson()

    override suspend fun create(args: Map<String, Any>): Notification? {
        if (!isCharging(context)) {
            return null
        }

        val serviceIntent = Intent(context, DeviceForegroundService::class.java).apply {
            val currentHeaders = NotificationContext.headers.get()
            if (currentHeaders != null) {
                putExtra("headers", gson.toJson(currentHeaders))
            }
        }

        ContextCompat.startForegroundService(context, serviceIntent)

        return null
    }

    private fun isCharging(context: Context): Boolean {
        val batteryIntent = context.registerReceiver(null, IntentFilter(Intent.ACTION_BATTERY_CHANGED))
        val status = batteryIntent?.getIntExtra(BatteryManager.EXTRA_STATUS, -1) ?: -1
        
        return status == BatteryManager.BATTERY_STATUS_CHARGING || status == BatteryManager.BATTERY_STATUS_FULL
    }
}
