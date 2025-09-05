package cz.lriedel.bridgex.geocoding

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.Service
import android.content.Intent
import android.os.IBinder
import android.util.Log
import androidx.core.app.NotificationCompat
import cz.lriedel.bridgex.R
import cz.lriedel.bridgex.authentication.AuthenticationService
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.cancel
import kotlinx.coroutines.launch

class GeocodingForegroundService : Service() {
    private val serviceScope = CoroutineScope(Dispatchers.IO + SupervisorJob())
    private val authenticationService by lazy { AuthenticationService(this) }
    private val geocodingService by lazy { GeocodingService(this, authenticationService) }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        startForeground(NOTIFICATION_ID, createNotification())

        serviceScope.launch {
            try {
                geocodingService.updateCurrentLocation()
            }
            catch (e: Exception) {
                Log.e(GeocodingForegroundService::class.java.simpleName, "An error occurred when uploading current location.", e)
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
            .setContentTitle(getString(R.string.title_current_location_update_started))
            .setContentText(getString(R.string.message_current_location_update_started))
            .setSmallIcon(R.drawable.ic_launcher_foreground)
            .setPriority(NotificationCompat.PRIORITY_LOW)
            .build()
    }

    companion object {
        private const val NOTIFICATION_ID = 1
        private const val CHANNEL_ID = "GeocodingForegroundService"
        private const val CHANNEL_NAME = "Current Location Update"
    }
}