package cz.lriedel.bridgex;

import android.content.Intent;
import android.webkit.WebResourceRequest;
import android.webkit.WebView;
import android.webkit.WebViewClient;

public class CustomWebViewClient extends WebViewClient {

    private final MainActivity mainActivity;
    private final String portalBaseUrl;

    public CustomWebViewClient(MainActivity mainActivity, String portalBaseUrl) {
        this.mainActivity = mainActivity;
        this.portalBaseUrl = portalBaseUrl;
    }

    @Override
    public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
        if (request.getUrl().toString().startsWith(portalBaseUrl)) {
            return false;
        }

        Intent intent = new Intent(Intent.ACTION_VIEW, request.getUrl());
        mainActivity.startActivity(intent);
        return true;
    }
}
