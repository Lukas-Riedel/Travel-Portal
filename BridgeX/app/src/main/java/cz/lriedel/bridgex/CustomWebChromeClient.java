package cz.lriedel.bridgex;

import android.webkit.GeolocationPermissions;
import android.webkit.WebChromeClient;

public class CustomWebChromeClient extends WebChromeClient {

    @Override
    public void onGeolocationPermissionsShowPrompt(String origin, GeolocationPermissions.Callback callback) {
        callback.invoke(origin, true, false);
    }
}
