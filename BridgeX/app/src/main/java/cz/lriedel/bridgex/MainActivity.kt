package cz.lriedel.bridgex

import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.view.View
import android.webkit.WebView
import androidx.activity.OnBackPressedCallback
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import com.google.android.gms.location.LocationRequest
import com.google.android.gms.location.LocationServices
import com.google.android.gms.location.Priority
import cz.lriedel.bridgex.authentication.AuthenticationService
import cz.lriedel.bridgex.device.DeviceInitializer
import cz.lriedel.bridgex.device.PassiveLocationReceiver
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

        registerPassiveLocationTrigger(this)

        webView.addJavascriptInterface(AndroidBridge(AuthenticationService.getOrCreate(this), deviceInitializer, this), ANDROID_BRIDGE_JAVASCRIPT_OBJECT_NAME)

        loadWebViewUrl(savedInstanceState, intent.getStringExtra("flight"), intent.getStringExtra("placeId"), intent.getStringExtra("tripId"), intent.getStringExtra("categoryId"),
            intent.getStringExtra("year"), intent.getStringExtra("task"), intent.getStringExtra("issues")?.toIntOrNull() ?: intent.getIntExtra("issues", 0))
        CoroutineScope(Dispatchers.IO).launch {
            deviceInitializer.initialize()
        }

        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                if (webView.canGoBack()) {
                    webView.goBack()
                }
                else {
                    isEnabled = false
                    onBackPressedDispatcher.onBackPressed()
                }
            }
        })
    }

    private fun registerPassiveLocationTrigger(context: Context) {
        val fusedLocationClient = LocationServices.getFusedLocationProviderClient(context)
        val locationRequest = LocationRequest.Builder(Priority.PRIORITY_PASSIVE, 1000)
            .setMinUpdateIntervalMillis(0)
            .build()

        val intent = Intent(context, PassiveLocationReceiver::class.java)
        val pendingIntent = PendingIntent.getBroadcast(context, 0, intent, PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_MUTABLE)

        try {
            fusedLocationClient.requestLocationUpdates(locationRequest, pendingIntent)
        }
        catch (e: SecurityException) {
            // Do nothing.
        }
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

    private fun loadWebViewUrl(savedInstanceState: Bundle?, flight: String?, placeId: String?, tripId: String?, categoryId: String?, year: String?, task: String?, issues: Int) {
        if (flight != null) {
            val flightUrl = "https://www.flightradar24.com/data/flights/$flight"
            val intent = Intent(Intent.ACTION_VIEW, Uri.parse(flightUrl)).apply {
                addFlags(Intent.FLAG_ACTIVITY_NEW_TASK) 
            }
            webView.context.startActivity(intent)

            return
        }
    
        val url = when {
            placeId != null -> "${BuildConfig.PORTAL_BASE_URL}place/$placeId"
            tripId != null -> "${BuildConfig.PORTAL_BASE_URL}trip/$tripId"
            categoryId != null -> "${BuildConfig.PORTAL_BASE_URL}category/$categoryId"
            year != null -> "${BuildConfig.PORTAL_BASE_URL}year/$year"
            issues > 0 -> "${BuildConfig.PORTAL_BASE_URL}admin?tab=issues"
            task != null -> "${BuildConfig.PORTAL_BASE_URL}admin?tab=tasks"
            else -> BuildConfig.PORTAL_BASE_URL
        }
        
        if (savedInstanceState == null) {
            val bustParam = System.currentTimeMillis() / (3600 * 1000)
            val separator = if (url.contains("?")) "&" else "?"
            webView.loadUrl("$url${separator}t=$bustParam")
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
