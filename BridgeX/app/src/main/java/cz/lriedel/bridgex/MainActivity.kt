package cz.lriedel.bridgex

import android.os.Bundle
import android.view.View
import android.webkit.WebView
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import cz.lriedel.bridgex.authentication.AuthenticationService
import cz.lriedel.bridgex.device.DeviceInitializer
import cz.lriedel.bridgex.ui.CustomWebChromeClient
import cz.lriedel.bridgex.ui.CustomWebViewClient

class MainActivity : AppCompatActivity() {

    private val webView: WebView by lazy { findViewById(R.id.webview) }
    private val permissionManager: PermissionManager by lazy { PermissionManager(this) }
    private val authenticationService: AuthenticationService by lazy { AuthenticationService(this) }
    private val deviceInitializer: DeviceInitializer by lazy { DeviceInitializer(this, authenticationService) }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        setupWebView()
        setupWindowInsets()
        permissionManager.requestAllPermissions()

        webView.addJavascriptInterface(AndroidBridge(authenticationService, deviceInitializer, this), ANDROID_BRIDGE_JAVASCRIPT_OBJECT_NAME)

        loadWebViewUrl(savedInstanceState, intent.getStringExtra("placeId"))
        deviceInitializer.initialize()
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

    private fun loadWebViewUrl(savedInstanceState: Bundle?, placeId: String?) {
        val url = BuildConfig.PORTAL_BASE_URL + placeId?.let { "place/$it" }.orEmpty()
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

    override fun onBackPressed() {
        if (webView.canGoBack()) {
            webView.goBack()
        }
        else {
            super.onBackPressed()
        }
    }

    override fun onRequestPermissionsResult(requestCode: Int, permissions: Array<String>, grantResults: IntArray) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        permissionManager.onRequestPermissionsResult(requestCode, permissions, grantResults)
    }

    companion object {
        private const val ANDROID_BRIDGE_JAVASCRIPT_OBJECT_NAME = "Android"
    }
}
