package cz.lriedel.bridgex;

import android.content.pm.PackageManager;
import android.os.Build;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;

public class PermissionManager {

    private static final int REQUEST_CODE_NOTIFICATIONS = 1001;
    private static final int REQUEST_CODE_FOREGROUND_LOCATION = 1002;
    private static final int REQUEST_CODE_BACKGROUND_LOCATION = 1003;


    private final MainActivity mainActivity;

    public PermissionManager(MainActivity mainActivity) {
        this.mainActivity = mainActivity;
    }

    public void requestAllPermissions(boolean silentMode) {
        if (!requestNotificationPermission()) {
            return;
        }
        if (!requestForegroundLocationPermission()) {
            return;
        }
        if (!requestBackgroundLocationPermission()) {
            return;
        }

        if (!silentMode) {
            Toast.makeText(mainActivity, R.string.all_permissions_granted, Toast.LENGTH_SHORT).show();
        }
    }

    private boolean requestNotificationPermission() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (ContextCompat.checkSelfPermission(mainActivity, android.Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
                ActivityCompat.requestPermissions(mainActivity,
                        new String[]{ android.Manifest.permission.POST_NOTIFICATIONS }, REQUEST_CODE_NOTIFICATIONS);
                return false;
            }
        }
        return true;
    }

    private boolean requestForegroundLocationPermission() {
        if (ContextCompat.checkSelfPermission(mainActivity, android.Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED
                || ContextCompat.checkSelfPermission(mainActivity, android.Manifest.permission.ACCESS_COARSE_LOCATION) != PackageManager.PERMISSION_GRANTED) {
            ActivityCompat.requestPermissions(mainActivity,
                    new String[]{ android.Manifest.permission.ACCESS_FINE_LOCATION, android.Manifest.permission.ACCESS_COARSE_LOCATION }, REQUEST_CODE_FOREGROUND_LOCATION);
            return false;
        }
        return true;
    }

    private boolean requestBackgroundLocationPermission() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            if (ContextCompat.checkSelfPermission(mainActivity, android.Manifest.permission.ACCESS_BACKGROUND_LOCATION)
                    != PackageManager.PERMISSION_GRANTED) {
                ActivityCompat.requestPermissions(mainActivity,
                        new String[]{ android.Manifest.permission.ACCESS_BACKGROUND_LOCATION }, REQUEST_CODE_BACKGROUND_LOCATION);
                return false;
            }
        }
        return true;
    }

    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions, @NonNull int[] grantResults) {
        if (requestCode == REQUEST_CODE_NOTIFICATIONS) {
            if (grantResults.length > 0 && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                Toast.makeText(mainActivity, R.string.notifications_granted, Toast.LENGTH_SHORT).show();
                requestAllPermissions(false);
            }
            else {
                Toast.makeText(mainActivity, R.string.notifications_denied, Toast.LENGTH_SHORT).show();
            }
        }
        else if (requestCode == REQUEST_CODE_FOREGROUND_LOCATION) {
            if (grantResults.length > 0 && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                Toast.makeText(mainActivity, R.string.foreground_location_granted, Toast.LENGTH_SHORT).show();
                requestAllPermissions(false);
            }
            else {
                Toast.makeText(mainActivity, R.string.foreground_location_denied, Toast.LENGTH_SHORT).show();
            }
        }
        else if (requestCode == REQUEST_CODE_BACKGROUND_LOCATION) {
            if (grantResults.length > 0 && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                Toast.makeText(mainActivity, R.string.background_location_granted, Toast.LENGTH_SHORT).show();
                requestAllPermissions(false);
            }
            else {
                Toast.makeText(mainActivity, R.string.background_location_denied, Toast.LENGTH_SHORT).show();
            }
        }
    }
}

