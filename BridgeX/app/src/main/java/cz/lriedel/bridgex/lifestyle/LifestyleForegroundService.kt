package cz.lriedel.bridgex.lifestyle

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.Service
import android.content.Intent
import android.os.IBinder
import android.util.Log
import androidx.core.app.NotificationCompat
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import cz.lriedel.bridgex.R
import cz.lriedel.bridgex.authentication.AuthenticationService
import cz.lriedel.bridgex.notification.NotificationContext
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.asContextElement
import kotlinx.coroutines.cancel
import kotlinx.coroutines.launch
import java.lang.reflect.Type

class LifestyleForegroundService : Service() {
    private val gson = Gson()
    private val mapGsonType: Type = object : TypeToken<Map<String?, Any?>?>() {}.type
    private val serviceScope = CoroutineScope(Dispatchers.IO + SupervisorJob())
    private val lifestyleService by lazy { LifestyleService.getOrCreate(this, AuthenticationService.getOrCreate(this)) }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        val intervals = intent?.getParcelableArrayListExtra("intervals", LifestyleInterval::class.java) ?: emptyList()
        val headers = gson.fromJson<Map<String, Any>>(intent?.getStringExtra("headers"), mapGsonType)

        startForeground(NOTIFICATION_ID, createNotification(intervals.size))

        serviceScope.launch(NotificationContext.headers.asContextElement(headers)) {
            try {
                for (interval in intervals) {
                    try {
                        lifestyleService.updateLifestyle(interval.start, interval.end, interval.height, interval.birthYear, interval.male)
                    }
                    catch (e: Exception) {
                        Log.e(LifestyleForegroundService::class.java.simpleName, "An error occurred when updating lifestyle.", e)
                    }
                }
            }
            finally {
                stopForeground(true)
                stopSelf()
            }
        }
        return START_NOT_STICKY
    }

    override fun onBind(intent: Intent?): IBinder? = null

    override fun onDestroy() {
        super.onDestroy()
        serviceScope.cancel()
    }

    private fun createNotification(itemsCount: Int): Notification {
        val channel = NotificationChannel(CHANNEL_ID, CHANNEL_NAME, NotificationManager.IMPORTANCE_LOW).apply {
            setSound(null, null)
            enableVibration(false)
        }

        val notificationManager: NotificationManager =
            getSystemService(NotificationManager::class.java)
        notificationManager.createNotificationChannel(channel)

        return NotificationCompat.Builder(this, CHANNEL_ID)
            .setContentTitle(getString(R.string.title_lifestyle_synchronization_started))
            .setContentText(getString(R.string.message_lifestyle_synchronization_started, itemsCount))
            .setSmallIcon(R.drawable.ic_launcher_foreground)
            .setPriority(NotificationCompat.PRIORITY_LOW)
            .build()
    }

    companion object {
        private const val NOTIFICATION_ID = 3
        private const val CHANNEL_ID = "LifestyleForegroundService"
        private const val CHANNEL_NAME = "Lifestyle Data Synchronization"
    }
}