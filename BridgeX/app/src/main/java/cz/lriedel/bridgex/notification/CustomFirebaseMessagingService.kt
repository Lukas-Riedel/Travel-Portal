package cz.lriedel.bridgex.notification

import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Intent
import android.util.Log
import androidx.core.app.NotificationCompat
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import cz.lriedel.bridgex.MainActivity
import cz.lriedel.bridgex.R
import cz.lriedel.bridgex.authentication.AuthenticationService
import cz.lriedel.bridgex.device.DeviceInitializer
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.lang.reflect.Type

class CustomFirebaseMessagingService : FirebaseMessagingService() {
    private val gson = Gson()
    private val mapGsonType: Type = object : TypeToken<Map<String?, Any?>?>() {}.type

    private val authenticationService by lazy { AuthenticationService(applicationContext) }
    private val deviceInitializer by lazy { DeviceInitializer(applicationContext, authenticationService) }
    private val notificationFactories: Map<String, NotificationFactory> by lazy {
        listOf(
            ProcessingEndedNotificationFactory(this),
            FitnessActivityDetectedNotificationFactory(this),
            LocationUpdateDetectedNotificationFactory(this)
        ).associateBy { factory ->
            factory.javaClass.simpleName.replace(NotificationFactory::class.java.simpleName, "")
        }
    }

    override fun onNewToken(token: String) {
        super.onNewToken(token)
        deviceInitializer.initialize(token)
    }

    override fun onMessageReceived(message: RemoteMessage) {
        super.onMessageReceived(message)

        if (message.data.isNotEmpty()) {
            Log.d(CustomFirebaseMessagingService::class.java.simpleName, "Received the message '${message.data}'...")

            val notificationFactory = notificationFactories[message.data["event"]]
            if (notificationFactory != null) {
                val args = gson.fromJson<Map<String, Any>>(message.data["args"], mapGsonType)
                CoroutineScope(Dispatchers.IO).launch {
                    val notification = notificationFactory.create(args)

                    notification?.let {
                        withContext(Dispatchers.Main) {
                            showNotification(it)
                        }
                    }
                }
            }

            // TODO: Send to UI somehow.
        }
    }

    private fun showNotification(notification: Notification) {
        val manager = getSystemService(NOTIFICATION_SERVICE) as NotificationManager

        val channel = NotificationChannel(CHANNEL_ID, CHANNEL_NAME, NotificationManager.IMPORTANCE_DEFAULT)
        manager.createNotificationChannel(channel)

        val intent = Intent(this, MainActivity::class.java)
        for ((key, value) in notification.intentExtras) {
            intent.putExtra(key, value.toString())
        }
        intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK)

        val pendingIntent = PendingIntent.getActivity(this, 0, intent, PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE)
        val builder = NotificationCompat.Builder(this, CHANNEL_ID)
            .setContentTitle(notification.title)
            .setContentText(notification.body)
            .setContentIntent(pendingIntent)
            .setSmallIcon(R.mipmap.ic_launcher)
            .setAutoCancel(true)

        manager.notify(System.currentTimeMillis().toInt(), builder.build())
    }

    companion object {
        private const val CHANNEL_ID = "CustomFirebaseMessagingService"
        private const val CHANNEL_NAME = "Alerts"
    }
}
