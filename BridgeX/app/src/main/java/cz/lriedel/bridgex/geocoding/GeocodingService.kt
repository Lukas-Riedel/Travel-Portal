package cz.lriedel.bridgex.geocoding

import android.content.Context
import android.util.Log
import android.Manifest
import android.content.pm.PackageManager
import androidx.core.content.ContextCompat
import com.google.android.gms.location.LocationServices
import cz.lriedel.bridgex.authentication.AuthenticationService
import kotlinx.coroutines.tasks.await
import cz.lriedel.bridgex.CoreClient.Companion.create

class GeocodingService(
    context: Context,
    authenticationService: AuthenticationService
) {
    private val coreClient = create(authenticationService)
    private val fusedLocationClient by lazy { LocationServices.getFusedLocationProviderClient(context) }

    suspend fun updateCurrentLocation() {
        Log.i(GeocodingService::class.java.simpleName, "Updating current location...")

        val location = fusedLocationClient.lastLocation.await()
        location?.let { coreClient.trackLocation(it.latitude, it.longitude) }
    }
}
