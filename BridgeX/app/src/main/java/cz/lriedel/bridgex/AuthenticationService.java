package cz.lriedel.bridgex;

import android.widget.Toast;

import androidx.annotation.Nullable;

public class AuthenticationService {

    @Nullable
    private String refreshToken;

    private final MainActivity mainActivity;

    public AuthenticationService(MainActivity mainActivity) {
        this.mainActivity = mainActivity;
    }

    public void setRefreshToken(String refreshToken) {
        this.refreshToken = refreshToken;

        // TODO: Remove
        Toast.makeText(mainActivity, "Refresh token byl nastaven na " + refreshToken, Toast.LENGTH_SHORT).show();
    }
}
