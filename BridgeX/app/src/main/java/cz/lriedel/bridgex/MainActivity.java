package cz.lriedel.bridgex;

import android.os.Bundle;
import android.view.View;
import android.webkit.WebSettings;
import android.webkit.WebView;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;

public class MainActivity extends AppCompatActivity {

    private static final String ANDROID_BRIDGE_JAVASCRIPT_OBJECT_NAME = "Android";
    private static final String PORTAL_BASE_URL = cz.lriedel.bridgex.BuildConfig.PORTAL_BASE_URL;

    private WebView webView;
    private PermissionManager permissionManager;
    private AuthenticationService authenticationService;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        setContentView(R.layout.activity_main);

        webView = findViewById(R.id.webview);

        View rootView = findViewById(android.R.id.content);
        ViewCompat.setOnApplyWindowInsetsListener(rootView, (view, insets) -> {
            WindowInsetsCompat insetsCompat = insets;
            view.setPadding(
                    insetsCompat.getInsets(WindowInsetsCompat.Type.systemBars()).left,
                    insetsCompat.getInsets(WindowInsetsCompat.Type.systemBars()).top,
                    insetsCompat.getInsets(WindowInsetsCompat.Type.systemBars()).right,
                    insetsCompat.getInsets(WindowInsetsCompat.Type.systemBars()).bottom
            );
            return insets;
        });

        authenticationService = new AuthenticationService(this);

        permissionManager = new PermissionManager(this);
        permissionManager.requestAllPermissions(true);

        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setJavaScriptCanOpenWindowsAutomatically(true);
        settings.setGeolocationEnabled(true);
        
        webView.setWebViewClient(new CustomWebViewClient(this, PORTAL_BASE_URL));
        webView.setWebChromeClient(new CustomWebChromeClient());

        webView.addJavascriptInterface(new AndroidBridge(permissionManager, authenticationService), ANDROID_BRIDGE_JAVASCRIPT_OBJECT_NAME);

        if (savedInstanceState == null) {
            webView.loadUrl(PORTAL_BASE_URL);
        }
    }

    @Override
    protected void onSaveInstanceState(Bundle outState) {
        super.onSaveInstanceState(outState);
        webView.saveState(outState);
    }

    @Override
    protected void onRestoreInstanceState(Bundle savedInstanceState) {
        super.onRestoreInstanceState(savedInstanceState);
        webView.restoreState(savedInstanceState);
    }

    @Override
    public void onBackPressed() {
        if (webView.canGoBack()) {
            webView.goBack();
        } else {
            super.onBackPressed();
        }
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions, @NonNull int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);
        permissionManager.onRequestPermissionsResult(requestCode, permissions, grantResults);
    }
}