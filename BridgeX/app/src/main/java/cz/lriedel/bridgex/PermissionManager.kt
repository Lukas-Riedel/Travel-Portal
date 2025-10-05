package cz.lriedel.bridgex

import android.Manifest
import android.content.pm.PackageManager
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import androidx.health.connect.client.HealthConnectClient
import androidx.health.connect.client.PermissionController
import androidx.health.connect.client.permission.HealthPermission
import androidx.health.connect.client.records.DistanceRecord
import androidx.health.connect.client.records.StepsRecord
import androidx.lifecycle.lifecycleScope
import kotlinx.coroutines.launch

class PermissionManager(
    private val mainActivity: MainActivity
) {
    private val healthClient = HealthConnectClient.getOrCreate(mainActivity)
    private val requiredHealthPermissions = arrayOf(
        HealthPermission.getReadPermission(StepsRecord::class.java.kotlin),
        HealthPermission.getReadPermission(DistanceRecord::class.java.kotlin)
    )
    private val healthPermissionLauncher = mainActivity.registerForActivityResult(
        PermissionController.createRequestPermissionResultContract()) {}

    // TODO: Make sure permissions are requested only once.
    // TODO: Make sure the application can operate even when missing some of the permissions.
    fun requestAllPermissions() {
        if (!requestNotificationPermission()) {
            return
        }
        if (!requestForegroundLocationPermission()) {
            return
        }
        if (!requestBackgroundLocationPermission()) {
            return
        }
        if (!requestHealthPermissions()) {
            return
        }
    }

    private fun requestNotificationPermission(): Boolean {
        if (ContextCompat.checkSelfPermission(mainActivity, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
            ActivityCompat.requestPermissions(mainActivity, arrayOf(Manifest.permission.POST_NOTIFICATIONS), REQUEST_CODE_NOTIFICATIONS)
            return false
        }
        return true
    }

    private fun requestForegroundLocationPermission(): Boolean {
        if (ContextCompat.checkSelfPermission(mainActivity, Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED
            || ContextCompat.checkSelfPermission(mainActivity, Manifest.permission.ACCESS_COARSE_LOCATION) != PackageManager.PERMISSION_GRANTED) {
            ActivityCompat.requestPermissions(mainActivity, arrayOf(Manifest.permission.ACCESS_FINE_LOCATION,
                Manifest.permission.ACCESS_COARSE_LOCATION), REQUEST_CODE_FOREGROUND_LOCATION
            )
            return false
        }
        return true
    }

    private fun requestBackgroundLocationPermission(): Boolean {
        if (ContextCompat.checkSelfPermission(mainActivity, Manifest.permission.ACCESS_BACKGROUND_LOCATION) != PackageManager.PERMISSION_GRANTED) {
            ActivityCompat.requestPermissions(mainActivity, arrayOf(Manifest.permission.ACCESS_BACKGROUND_LOCATION),
                REQUEST_CODE_BACKGROUND_LOCATION)
            return false
        }
        return true
    }

    private fun requestHealthPermissions(): Boolean {
        mainActivity.lifecycleScope.launch {
            val granted = healthClient.permissionController.getGrantedPermissions()
            if (!granted.containsAll(requiredHealthPermissions.toList())) {
                healthPermissionLauncher.launch(requiredHealthPermissions.toSet())
            }
        }

        if (ContextCompat.checkSelfPermission(mainActivity, Manifest.permission.ACTIVITY_RECOGNITION) != PackageManager.PERMISSION_GRANTED) {
            ActivityCompat.requestPermissions(mainActivity, arrayOf(Manifest.permission.ACTIVITY_RECOGNITION),
                REQUEST_CODE_ACTIVITY_RECOGNITION)
            return false
        }
        return true
    }

    fun onRequestPermissionsResult(requestCode: Int, permissions: Array<String>, grantResults: IntArray) {
        if (requestCode == REQUEST_CODE_NOTIFICATIONS) {
            if (grantResults.isNotEmpty() && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                requestAllPermissions()
            }
        }
        else if (requestCode == REQUEST_CODE_FOREGROUND_LOCATION) {
            if (grantResults.isNotEmpty() && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                requestAllPermissions()
            }
        }
        else if (requestCode == REQUEST_CODE_BACKGROUND_LOCATION) {
            if (grantResults.isNotEmpty() && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                requestAllPermissions()
            }
        }
    }

    companion object {
        private const val REQUEST_CODE_NOTIFICATIONS = 1001
        private const val REQUEST_CODE_FOREGROUND_LOCATION = 1002
        private const val REQUEST_CODE_BACKGROUND_LOCATION = 1003
        private const val REQUEST_CODE_ACTIVITY_RECOGNITION = 1004
    }
}

