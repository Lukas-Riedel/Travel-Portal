package cz.lriedel.bridgex.device

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import androidx.core.content.ContextCompat
import com.google.android.gms.location.LocationResult

class PassiveLocationReceiver : BroadcastReceiver() {

    override fun onReceive(context: Context, intent: Intent) {
        if (!LocationResult.hasResult(intent)) {
            return
        }

        val preferences = context.getSharedPreferences(DEVICE_PREFERENCES_NAME, Context.MODE_PRIVATE)
        val currentTime = System.currentTimeMillis()
        
        val lastFcmTime = preferences.getLong(LAST_NOTIFICATION_TIMESTAMP_KEY, 0L)
        val lastLogonTime = preferences.getLong(LAST_LOGON_TIMESTAMP_KEY, 0L)

        val isNotificationActive = (currentTime - lastFcmTime) <= MAX_NOTIFICATION_AGE_MS
        val isThrottled = (currentTime - lastLogonTime) < MIN_LOGON_INTERVAL_MS

        if (isNotificationActive && !isThrottled) {
            preferences.edit().putLong(LAST_LOGON_TIMESTAMP_KEY, currentTime).apply()

            val serviceIntent = Intent(context, DeviceForegroundService::class.java)
            ContextCompat.startForegroundService(context, serviceIntent)
        }
    }

    companion object {
        private const val DEVICE_PREFERENCES_NAME = "DevicePreferences"
        private const val LAST_LOGON_TIMESTAMP_KEY = "lastLogonTimestamp"
        private const val LAST_NOTIFICATION_TIMESTAMP_KEY = "lastNotificationTimestamp"
        private const val MIN_LOGON_INTERVAL_MS = 15 * 60 * 1000L
        private const val MAX_NOTIFICATION_AGE_MS = 24 * 60 * 60 * 1000L
    }
}