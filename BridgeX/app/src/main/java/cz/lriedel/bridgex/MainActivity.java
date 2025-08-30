package cz.lriedel.bridgex;

import android.os.Bundle;
import android.webkit.WebChromeClient;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;

import androidx.appcompat.app.AppCompatActivity;

public class MainActivity extends AppCompatActivity {

    private WebView webView;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        webView = findViewById(R.id.webview);

        // základní nastavení
        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setAllowContentAccess(true);
        settings.setAllowFileAccess(true);
        settings.setJavaScriptCanOpenWindowsAutomatically(true);

        // WebViewClient zajišťuje, že linky zůstanou ve WebView
        webView.setWebViewClient(new WebViewClient());

        // volitelně WebChromeClient pro podporu alertů, console.log, apod.
        webView.setWebChromeClient(new WebChromeClient());

        // načtení HTTPS stránky
        webView.loadUrl("https://lriedel.cz");
    }

    @Override
    public void onBackPressed() {
        // umožní návrat zpět ve WebView historii
        if (webView.canGoBack()) {
            webView.goBack();
        } else {
            super.onBackPressed();
        }
    }
}