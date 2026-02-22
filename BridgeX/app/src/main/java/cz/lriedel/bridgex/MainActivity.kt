package cz.lriedel.bridgex

import android.os.Bundle
import android.view.View
import android.webkit.WebView
import androidx.activity.OnBackPressedCallback
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import cz.lriedel.bridgex.authentication.AuthenticationService
import cz.lriedel.bridgex.device.DeviceInitializer
import cz.lriedel.bridgex.ui.CustomWebChromeClient
import cz.lriedel.bridgex.ui.CustomWebViewClient
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch

class MainActivity : AppCompatActivity() {

    private val webView: WebView by lazy { findViewById(R.id.webview) }
    private val permissionManager: PermissionManager by lazy { PermissionManager(this) }
    private val deviceInitializer: DeviceInitializer by lazy { DeviceInitializer(this, AuthenticationService.getOrCreate(this)) }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        setupWebView()
        setupWindowInsets()
        
        permissionManager.requestAllPermissions()

        webView.addJavascriptInterface(AndroidBridge(AuthenticationService.getOrCreate(this), deviceInitializer, this), ANDROID_BRIDGE_JAVASCRIPT_OBJECT_NAME)

        loadWebViewUrl(savedInstanceState, intent.getStringExtra("placeId"), intent.getStringExtra("tripId"), intent.getStringExtra("categoryId"), intent.getStringExtra("year"))
        CoroutineScope(Dispatchers.IO).launch {
            deviceInitializer.initialize()
        }

        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                if (webView.canGoBack()) {
                    webView.goBack()
                } else {
                    isEnabled = false
                    onBackPressedDispatcher.onBackPressed()
                }
            }
        })
    }

    private fun setupWebView() {
        webView.settings.apply {
            javaScriptEnabled = true
            domStorageEnabled = true
            javaScriptCanOpenWindowsAutomatically = true
            setGeolocationEnabled(true)
        }
        webView.webViewClient = CustomWebViewClient(this)
        webView.webChromeClient = CustomWebChromeClient()
    }

    private fun setupWindowInsets() {
        val rootView = findViewById<View>(android.R.id.content)
        ViewCompat.setOnApplyWindowInsetsListener(rootView) { view, insets ->
            val systemInsets = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            view.setPadding(systemInsets.left, systemInsets.top, systemInsets.right, systemInsets.bottom)
            insets
        }
    }

    private fun loadWebViewUrl(savedInstanceState: Bundle?, placeId: String?, tripId: String?, categoryId: String?, year: String?) {
        val path = when {
            placeId != null -> "place/$placeId"
            tripId != null -> "trip/$tripId"
            categoryId != null -> "category/$categoryId"
            year != null -> "year/$year"
            else -> "" 
        }

        val url = "${BuildConfig.PORTAL_BASE_URL}$path"
        
        if (savedInstanceState == null) {
            val bustParam = System.currentTimeMillis() / (3600 * 1000)
            webView.loadUrl("$url?t=$bustParam")
        }
        else {
            webView.restoreState(savedInstanceState)
        }
    }

    override fun onSaveInstanceState(outState: Bundle) {
        super.onSaveInstanceState(outState)
        webView.saveState(outState)
    }

    override fun onRequestPermissionsResult(requestCode: Int, permissions: Array<String>, grantResults: IntArray) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        permissionManager.onRequestPermissionsResult(requestCode, permissions, grantResults)
    }

    companion object {
        private const val ANDROID_BRIDGE_JAVASCRIPT_OBJECT_NAME = "Android"
    }
}
