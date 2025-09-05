package cz.lriedel.bridgex.notification

import android.content.Intent
import androidx.core.content.ContextCompat
import android.content.Context
import android.Manifest
import android.content.pm.PackageManager
import com.google.android.gms.location.LocationServices
import kotlinx.coroutines.tasks.await
import cz.lriedel.bridgex.authentication.AuthenticationService
import cz.lriedel.bridgex.geocoding.GeocodingForegroundService

class LocationUpdateDetectedNotificationFactory(
    private val context: Context
) : NotificationFactory {

    override suspend fun create(args: Map<String, Any>): Notification? {
        val serviceIntent = Intent(context, GeocodingForegroundService::class.java)

        ContextCompat.startForegroundService(context, serviceIntent)

        return null
    }
}
