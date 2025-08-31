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

    private WebView webView;
    private PermissionManager permissionManager;

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

        permissionManager = new PermissionManager(this);
        permissionManager.requestAllPermissions(true);

        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setJavaScriptCanOpenWindowsAutomatically(true);
        settings.setGeolocationEnabled(true);

        AuthenticationService authenticationService = new AuthenticationService(this);
        DeviceInitializer deviceInitializer = new DeviceInitializer(this, authenticationService);
        webView.setWebViewClient(new CustomWebViewClient(this));
        webView.setWebChromeClient(new CustomWebChromeClient());
        webView.addJavascriptInterface(new AndroidBridge(permissionManager, authenticationService, deviceInitializer), ANDROID_BRIDGE_JAVASCRIPT_OBJECT_NAME);

        if (savedInstanceState == null) {
            webView.loadUrl(cz.lriedel.bridgex.BuildConfig.PORTAL_BASE_URL);
        }

        deviceInitializer.initialize("TODO");
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