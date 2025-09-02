package cz.lriedel.bridgex.device

import android.content.Context
import android.os.Build
import android.provider.Settings
import android.util.Log
import com.google.firebase.messaging.FirebaseMessaging
import cz.lriedel.bridgex.CoreClient
import cz.lriedel.bridgex.CoreClient.Companion.create
import cz.lriedel.bridgex.authentication.AuthenticationService
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch

class DeviceInitializer(
    context: Context,
    authenticationService: AuthenticationService
) {
    private val deviceName: String = getPrettyDeviceName(context)
    private val coreClient: CoreClient = create(authenticationService)

    fun initialize(fcmToken: String) {
        Log.d(DeviceInitializer::class.java.simpleName, "Received a request to initialize a device...")

        for (deviceType in DeviceType.entries) {
            CoroutineScope(Dispatchers.IO).launch {
                try {
                    coreClient.createDevice(DeviceRequest(deviceType.value, deviceName, fcmToken))
                }
                catch (e: Exception) {
                    Log.e(DeviceInitializer::class.java.simpleName, "An error occurred when initializing a device.", e)
                }
            }
        }
    }

    fun initialize() {
        FirebaseMessaging.getInstance().token
            .addOnCompleteListener { task ->
                if (!task.isSuccessful) {
                    return@addOnCompleteListener
                }
                val fcmToken = task.result ?: return@addOnCompleteListener

                initialize(fcmToken)
            }
    }

    companion object {
        private const val DEVICE_NAME_KEY = "device_name"

        private fun getPrettyDeviceName(context: Context): String {
            val deviceNameSetting = Settings.Global.getString(context.contentResolver, DEVICE_NAME_KEY)
            if (deviceNameSetting != null && deviceNameSetting.isNotEmpty()) {
                return deviceNameSetting
            }

            return Build.MANUFACTURER + " " + Build.MODEL
        }
    }
}
