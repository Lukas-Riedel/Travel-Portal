package cz.lriedel.bridgex;

import android.content.Context;
import android.content.SharedPreferences;
import android.util.Log;

import androidx.annotation.Nullable;

import com.google.gson.GsonBuilder;

import java.io.IOException;

import okhttp3.ResponseBody;
import retrofit2.Response;
import retrofit2.Retrofit;
import retrofit2.converter.gson.GsonConverterFactory;

public class AuthenticationService {

    private static final String AUTHENTICATION_PREFERENCES_NAME = "AuthenticationPreferences";
    private static final String REFRESH_TOKEN_KEY = "RefreshToken";

    private final SharedPreferences sharedPreferences;
    private final AuthenticationClient authenticationClient;

    public AuthenticationService(Context context) {
        this.sharedPreferences = context.getSharedPreferences(AUTHENTICATION_PREFERENCES_NAME, Context.MODE_PRIVATE);
        this.authenticationClient = new Retrofit.Builder()
                .baseUrl(cz.lriedel.bridgex.BuildConfig.IAM_BASE_URL)
                .addConverterFactory(GsonConverterFactory.create(new GsonBuilder().setLenient().create()))
                .build()
                .create(AuthenticationClient.class);
    }

    public void setRefreshToken(String refreshToken) {
        sharedPreferences.edit().putString(REFRESH_TOKEN_KEY, refreshToken).apply();
    }

    @Nullable
    public String getAccessToken() {
        // TODO: Introduce caching of access token if needed.
        String refreshToken = sharedPreferences.getString(REFRESH_TOKEN_KEY, null);
        if (refreshToken == null) {
            return null;
        }

        try {
            Response<AccessToken> accessTokenResponse = authenticationClient.createAccessToken(cz.lriedel.bridgex.BuildConfig.IAM_BASE_URL,
                    new AccessTokenRequest(refreshToken)).execute();
            if (!accessTokenResponse.isSuccessful()) {
                return null;
            }

            AccessToken accessToken = accessTokenResponse.body();
            if (accessToken == null) {
                return null;
            }

            setRefreshToken(accessToken.refreshToken());

            return accessToken.accessToken();
        } catch (IOException e) {
            return null;
        }
    }
}
