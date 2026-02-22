package cz.lriedel.bridgex.device

import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.content.SharedPreferences
import android.location.Location
import android.os.BatteryManager
import android.os.Build
import android.provider.Settings
import android.util.Log
import androidx.core.content.ContextCompat
import com.google.android.gms.location.CurrentLocationRequest
import com.google.android.gms.location.LocationServices
import com.google.android.gms.location.Priority
import com.google.android.gms.tasks.CancellationTokenSource
import com.google.firebase.messaging.FirebaseMessaging
import cz.lriedel.bridgex.CoreClient
import cz.lriedel.bridgex.authentication.AuthenticationService
import cz.lriedel.bridgex.geocoding.AddressResponse
import kotlinx.coroutines.tasks.await

class DeviceInitializer(
    private val context: Context,
    private val authenticationService: AuthenticationService
) {
    private val sharedPreferences: SharedPreferences =
        context.getSharedPreferences(DEVICE_PREFERENCES_NAME, Context.MODE_PRIVATE)
    private val deviceName: String = getPrettyDeviceName(context)
    private val deviceId: String = getOrCreateDeviceId()
    private val coreClient: CoreClient = CoreClient.getOrCreate(authenticationService)
    private val fusedLocationClient by lazy { LocationServices.getFusedLocationProviderClient(context) }

    suspend fun initialize(fcmToken: String) {
        Log.d(DeviceInitializer::class.java.simpleName, "Received a request to initialize a device...")

        try {
            val deviceData = createData(fcmToken)
            for (deviceType in DeviceType.entries) {
                coreClient.createDevice(DeviceRequest(deviceId, deviceType.value, deviceName, deviceData))
            }
        }
        catch (e: Exception) {
            Log.e(DeviceInitializer::class.java.simpleName, "An error occurred when initializing a device.", e)
        }
    }

    suspend fun initialize() {
        val fcmToken = FirebaseMessaging.getInstance().token.await()

        if (fcmToken != null) {
            initialize(fcmToken)
        }
    }

    private suspend fun createData(fcmToken: String): DeviceData {
        if (!authenticationService.hasUserRoles(BRIDGEX_LOCATION_READ_ROLE)) {
            return DeviceData(fcmToken, null, null, null, null, null)
        }

        val batteryStatus: Intent? = ContextCompat.getSystemService(context, BatteryManager::class.java)?.let { _ ->
            val intentFilter = IntentFilter(Intent.ACTION_BATTERY_CHANGED)
            context.registerReceiver(null, intentFilter)
        }
        val batteryLevel = batteryStatus?.getIntExtra(BatteryManager.EXTRA_LEVEL, -1) ?: -1
        val batteryScale = batteryStatus?.getIntExtra(BatteryManager.EXTRA_SCALE, -1) ?: -1

        val location = getLocation(batteryLevel / batteryScale.toDouble() * 100)

        var address: AddressResponse? = null
        if (location != null) {
            try {
                address = coreClient.getAddress(location.latitude, location.longitude)
            } catch (e: Exception) {
                Log.e(DeviceInitializer::class.java.simpleName, "An error occurred when obtaining address.", e)
            }
        }

        return DeviceData(fcmToken, location?.latitude, location?.longitude, DeviceAddressData(address?.address, address?.address),
            java.util.TimeZone.getDefault().id, batteryLevel / batteryScale.toDouble() * 100)
    }

    private suspend fun getLocation(batteryLevel: Double): Location? {
        val lastLocation = fusedLocationClient.lastLocation.await()
        if (batteryLevel < LOCATION_FETCH_BATTERY_THRESHOLD
            || (lastLocation != null && System.currentTimeMillis() - lastLocation.time <= MAX_LOCATION_AGE_MILLISECONDS)) {
            return lastLocation
        }

        val currentLocationRequest = CurrentLocationRequest.Builder()
            .setPriority(Priority.PRIORITY_BALANCED_POWER_ACCURACY)
            .setDurationMillis(LOCATION_FETCH_TIMEOUT_MILLISECONDS)
            .setMaxUpdateAgeMillis(0)
            .build()

        val cancellationToken = CancellationTokenSource().token
        val newLocation = fusedLocationClient.getCurrentLocation(currentLocationRequest, cancellationToken).await()
        if (newLocation != null) {
            return newLocation
        }

        return lastLocation
    }

    private fun getOrCreateDeviceId(): String {
        var deviceId = sharedPreferences.getString(DEVICE_ID_KEY, null)
        if (deviceId == null) {
            deviceId = java.util.UUID.randomUUID().toString()
            sharedPreferences.edit().putString(DEVICE_ID_KEY, deviceId).apply()
        }
        return deviceId
    }

    companion object {
        private const val DEVICE_PREFERENCES_NAME = "DevicePreferences"
        private const val DEVICE_ID_KEY = "deviceId"
        private const val DEVICE_NAME_KEY = "device_name"
        private const val BRIDGEX_LOCATION_READ_ROLE = "bridgex.location.read"
        private const val MAX_LOCATION_AGE_MILLISECONDS = 30 * 60 * 1000L
        private const val LOCATION_FETCH_TIMEOUT_MILLISECONDS = 10 * 1000L
        private const val LOCATION_FETCH_BATTERY_THRESHOLD = 30

        private fun getPrettyDeviceName(context: Context): String {
            val deviceNameSetting = Settings.Global.getString(context.contentResolver, DEVICE_NAME_KEY)
            if (deviceNameSetting != null && deviceNameSetting.isNotEmpty()) {
                return deviceNameSetting
            }

            return Build.MANUFACTURER + " " + Build.MODEL
        }
    }
}
