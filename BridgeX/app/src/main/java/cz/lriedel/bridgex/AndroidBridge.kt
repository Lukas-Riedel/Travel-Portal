package cz.lriedel.bridgex

import android.webkit.JavascriptInterface
import cz.lriedel.bridgex.authentication.AuthenticationService
import cz.lriedel.bridgex.device.DeviceInitializer
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import androidx.lifecycle.lifecycleScope

class AndroidBridge(
    private val authenticationService: AuthenticationService,
    private val deviceInitializer: DeviceInitializer,
    private val mainActivity: MainActivity
) {
    @JavascriptInterface
    fun login(username: String?, password: String?) {
        mainActivity.lifecycleScope.launch(Dispatchers.IO) {
            authenticationService.login(username, password)
            deviceInitializer.initialize()
        }
    }
    
    @JavascriptInterface
    fun logout() {
        authenticationService.logout()
        deviceInitializer.initialize()
    }
}
