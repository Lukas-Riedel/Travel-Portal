package cz.lriedel.bridgex;

import android.webkit.JavascriptInterface;

public class AndroidBridge {

    private final PermissionManager permissionManager;
    private final AuthenticationService authenticationService;
    private final DeviceInitializer deviceInitializer;

    public AndroidBridge(PermissionManager permissionManager, AuthenticationService authenticationService, DeviceInitializer deviceInitializer) {
        this.permissionManager = permissionManager;
        this.authenticationService = authenticationService;
        this.deviceInitializer = deviceInitializer;
    }

    @JavascriptInterface
    public void setRefreshToken(String refreshToken) {
        authenticationService.setRefreshToken(refreshToken);
        deviceInitializer.initialize("TODO");
    }

    @JavascriptInterface
    public void requestAllPermissions() {
        permissionManager.requestAllPermissions(false);
    }
}
