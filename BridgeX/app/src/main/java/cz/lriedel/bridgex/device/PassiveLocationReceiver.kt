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

        val prefs = context.getSharedPreferences(DEVICE_PREFERENCES_NAME, Context.MODE_PRIVATE)
        val lastLogonTime = prefs.getLong(LAST_LOGON_KEY, 0L)
        val currentTime = System.currentTimeMillis()

        if (currentTime - lastLogonTime >= MIN_LOGON_INTERVAL_MS) {
            prefs.edit().putLong(LAST_LOGON_KEY, currentTime).apply()

            val serviceIntent = Intent(context, DeviceForegroundService::class.java)
            ContextCompat.startForegroundService(context, serviceIntent)
        }
    }

    companion object {
        private const val DEVICE_PREFERENCES_NAME = "DevicePreferences"
        private const val LAST_LOGON_KEY = "lastLogonTimestamp"
        private const val MIN_LOGON_INTERVAL_MS = 15 * 60 * 1000L
    }
}