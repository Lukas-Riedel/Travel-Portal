package cz.lriedel.bridgex;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.webkit.WebSettings;
import android.webkit.WebView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;

import com.google.firebase.FirebaseApp;
import com.google.firebase.FirebaseOptions;

public class MainActivity extends AppCompatActivity {

    private static final String ANDROID_BRIDGE_JAVASCRIPT_OBJECT_NAME = "Android";

    @Nullable
    private WebView webView;
    @Nullable
    private PermissionManager permissionManager;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        FirebaseOptions options = new FirebaseOptions.Builder()
                .setApiKey(cz.lriedel.bridgex.BuildConfig.FIREBASE_API_KEY)
                .setApplicationId(cz.lriedel.bridgex.BuildConfig.FIREBASE_APP_ID)
                .setProjectId(cz.lriedel.bridgex.BuildConfig.FIREBASE_PROJECT_ID)
                .setStorageBucket(cz.lriedel.bridgex.BuildConfig.FIREBASE_STORAGE_BUCKET)
                .setGcmSenderId(cz.lriedel.bridgex.BuildConfig.FIREBASE_MESSAGING_SENDER_ID)
                .build();

        if (FirebaseApp.getApps(this).isEmpty()) {
            FirebaseApp.initializeApp(this, options);
        }

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

        String webViewUrl = cz.lriedel.bridgex.BuildConfig.PORTAL_BASE_URL;

        Intent intent = getIntent();
        if (intent != null && intent.hasExtra("placeId")) {
            webViewUrl += "place/" + intent.getStringExtra("placeId");
        }

        if (savedInstanceState == null) {
            webView.loadUrl(webViewUrl);
        }

        deviceInitializer.initialize();
    }

    @Override
    protected void onSaveInstanceState(Bundle outState) {
        super.onSaveInstanceState(outState);

        if (webView != null) {
            webView.saveState(outState);
        }
    }

    @Override
    protected void onRestoreInstanceState(Bundle savedInstanceState) {
        super.onRestoreInstanceState(savedInstanceState);

        if (webView != null) {
            webView.restoreState(savedInstanceState);
        }
    }

    @Override
    public void onBackPressed() {
        if (webView != null && webView.canGoBack()) {
            webView.goBack();
        }
        else {
            super.onBackPressed();
        }
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions, @NonNull int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);

        if (permissionManager != null) {
            permissionManager.onRequestPermissionsResult(requestCode, permissions, grantResults);
        }
    }
}