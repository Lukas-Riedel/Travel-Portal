package cz.lriedel.bridgex.device

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

class DeviceForegroundService : Service() {
    private val gson = Gson()
    private val mapGsonType: Type = object : TypeToken<Map<String?, Any?>?>() {}.type
    private val serviceScope = CoroutineScope(Dispatchers.IO + SupervisorJob())
    private val deviceInitializer by lazy { DeviceInitializer(this, AuthenticationService.getOrCreate(this)) }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        val headers = gson.fromJson<Map<String, Any>>(intent?.getStringExtra("headers"), mapGsonType)

        startForeground(NOTIFICATION_ID, createNotification())

        serviceScope.launch(NotificationContext.headers.asContextElement(headers)) {
            try {
                deviceInitializer.initialize()
            }
            catch (e: Exception) {
                Log.e(DeviceForegroundService::class.java.simpleName, "An error occurred when initializing the device.", e)
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

    private fun createNotification(): Notification {
        val channel = NotificationChannel(CHANNEL_ID, CHANNEL_NAME, NotificationManager.IMPORTANCE_LOW).apply {
            setSound(null, null)
            enableVibration(false)
        }

        val notificationManager: NotificationManager =
            getSystemService(NotificationManager::class.java)
        notificationManager.createNotificationChannel(channel)

        return NotificationCompat.Builder(this, CHANNEL_ID)
            .setContentTitle(getString(R.string.title_device_logon_started))
            .setContentText(getString(R.string.message_device_logon_started))
            .setSmallIcon(R.drawable.ic_launcher_foreground)
            .setPriority(NotificationCompat.PRIORITY_LOW)
            .build()
    }

    companion object {
        private const val NOTIFICATION_ID = 1
        private const val CHANNEL_ID = "DeviceForegroundService"
        private const val CHANNEL_NAME = "Current Location Update"
    }
}