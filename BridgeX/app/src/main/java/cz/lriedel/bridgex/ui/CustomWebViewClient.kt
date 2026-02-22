package cz.lriedel.bridgex.ui

import android.content.Intent
import android.webkit.WebResourceRequest
import android.webkit.WebView
import android.webkit.WebViewClient
import cz.lriedel.bridgex.BuildConfig
import cz.lriedel.bridgex.MainActivity

class CustomWebViewClient(
    private val mainActivity: MainActivity
) : WebViewClient() {
    override fun shouldOverrideUrlLoading(view: WebView, request: WebResourceRequest): Boolean {
        if (request.url.toString().startsWith(BuildConfig.PORTAL_BASE_URL)) {
            return false
        }

        val intent = Intent(Intent.ACTION_VIEW, request.url)
        mainActivity.startActivity(intent)
        return true
    }
}
