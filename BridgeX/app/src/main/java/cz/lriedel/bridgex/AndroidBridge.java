package cz.lriedel.bridgex;

import android.webkit.JavascriptInterface;

public class AndroidBridge {

    private final PermissionManager permissionManager;
    private final AuthenticationService authenticationService;

    public AndroidBridge(PermissionManager permissionManager, AuthenticationService authenticationService) {
        this.permissionManager = permissionManager;
        this.authenticationService = authenticationService;
    }

    @JavascriptInterface
    public void setRefreshToken(String refreshToken) {
        authenticationService.setRefreshToken(refreshToken);
    }

    @JavascriptInterface
    public void requestAllPermissions() {
        permissionManager.requestAllPermissions(false);
    }
}
