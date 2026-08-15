package cz.lriedel.bridgex.device

import android.content.Context
import android.content.Intent
import androidx.core.content.ContextCompat
import com.google.gson.Gson

class DeviceForegroundServiceRunner(
    private val context: Context
) {
    private val preferences = context.getSharedPreferences(DEVICE_PREFERENCES_NAME, Context.MODE_PRIVATE)
    private val gson = Gson()

    fun recordExecutionAttempt() {
        preferences.edit().putLong(LAST_EXECUTION_ATTEMPT_TIMESTAMP_KEY, System.currentTimeMillis()).apply()
    }

    fun execute(headers: Map<String, Any>? = null): Boolean {
        val currentTime = System.currentTimeMillis()
        val lastExecutionAttemptTime = preferences.getLong(LAST_EXECUTION_ATTEMPT_TIMESTAMP_KEY, 0L)
        val lastSuccessfulExecutionTime = preferences.getLong(LAST_SUCCESSFUL_EXECUTION_TIMESTAMP_KEY, 0L)

        val shouldBeExecuted = (currentTime - lastExecutionAttemptTime) <= MAX_LAST_EXECUTION_ATTEMPT_AGE_MS
        val isThrottled = (currentTime - lastSuccessfulExecutionTime) < MIN_EXECUTION_INTERVAL_MS

        if (shouldBeExecuted && !isThrottled) {
            preferences.edit().putLong(LAST_SUCCESSFUL_EXECUTION_TIMESTAMP_KEY, currentTime).commit()

            val serviceIntent = Intent(context, DeviceForegroundService::class.java).apply {
                if (headers != null) {
                    putExtra("headers", gson.toJson(headers))
                }
            }
            ContextCompat.startForegroundService(context, serviceIntent)

            return true
        }

        return false
    }

    companion object {
        private const val DEVICE_PREFERENCES_NAME = "DevicePreferences"
        private const val LAST_SUCCESSFUL_EXECUTION_TIMESTAMP_KEY = "lastSuccessfulExecutionTime"
        private const val LAST_EXECUTION_ATTEMPT_TIMESTAMP_KEY = "lastExecutionAttemptTime"
        
        private const val MIN_EXECUTION_INTERVAL_MS = 15 * 60 * 1000L
        private const val MAX_LAST_EXECUTION_ATTEMPT_AGE_MS = 12 * 60 * 60 * 1000L
    }
}