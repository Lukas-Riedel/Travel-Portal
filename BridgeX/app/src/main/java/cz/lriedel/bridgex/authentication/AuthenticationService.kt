package cz.lriedel.bridgex.authentication

import android.content.Context
import android.content.SharedPreferences
import android.util.Log
import com.google.gson.GsonBuilder
import cz.lriedel.bridgex.BuildConfig
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory

class AuthenticationService(context: Context) {
    private val sharedPreferences: SharedPreferences =
        context.getSharedPreferences(AUTHENTICATION_PREFERENCES_NAME, Context.MODE_PRIVATE)
    private val authenticationClient: AuthenticationClient = Retrofit.Builder()
        .baseUrl(BuildConfig.IAM_BASE_URL)
        .addConverterFactory(GsonConverterFactory.create(GsonBuilder().setLenient().create()))
        .build()
        .create(AuthenticationClient::class.java)

    fun setRefreshToken(refreshToken: String?) {
        Log.d(AuthenticationService::class.java.simpleName, "Setting refresh token to $refreshToken...")
        sharedPreferences.edit().putString(REFRESH_TOKEN_KEY, refreshToken).apply()
    }

    // TODO: Introduce access token caching.
    suspend fun getAccessToken(): String? {
        Log.d(AuthenticationService::class.java.simpleName, "Received a request to obtain an access token...")
        val refreshToken = sharedPreferences.getString(REFRESH_TOKEN_KEY, null) ?: return null

        return try {
            val accessToken = authenticationClient.createAccessToken(BuildConfig.IAM_BASE_URL, AccessTokenRequest(refreshToken))
            setRefreshToken(accessToken.refreshToken)
            accessToken.accessToken
        } catch (e: Exception) {
            null
        }
    }

    companion object {
        private const val AUTHENTICATION_PREFERENCES_NAME = "AuthenticationPreferences"
        private const val REFRESH_TOKEN_KEY = "RefreshToken"
    }
}
