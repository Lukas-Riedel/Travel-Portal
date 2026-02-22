package cz.lriedel.bridgex

import android.content.Intent
import android.util.Log
import android.webkit.JavascriptInterface
import androidx.lifecycle.lifecycleScope
import cz.lriedel.bridgex.authentication.AuthenticationService
import cz.lriedel.bridgex.device.DeviceInitializer
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch

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
    fun share(title: String?, url: String?) {
        try {
            val intent = Intent(Intent.ACTION_SEND).apply {
                type = "text/plain"
                putExtra(Intent.EXTRA_SUBJECT, title)
                putExtra(Intent.EXTRA_TEXT, url)
            }

            val chooser = Intent.createChooser(intent, title).apply {
                addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
            }

            mainActivity.startActivity(chooser)
        } 
        catch (e: Exception) {
            Log.e(AndroidBridge::class.java.simpleName, "An error occurred when sharing a link.", e)
        }
    }
}
