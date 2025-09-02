package cz.lriedel.bridgex

import android.webkit.JavascriptInterface
import cz.lriedel.bridgex.authentication.AuthenticationService
import cz.lriedel.bridgex.device.DeviceInitializer

class AndroidBridge(
    private val authenticationService: AuthenticationService,
    private val deviceInitializer: DeviceInitializer
) {
    @JavascriptInterface
    fun setRefreshToken(refreshToken: String?) {
        authenticationService.setRefreshToken(refreshToken)
        deviceInitializer.initialize()
    }
}
