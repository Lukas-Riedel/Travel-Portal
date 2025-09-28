package cz.lriedel.bridgex.authentication

import android.content.Context
import android.content.SharedPreferences
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKeys
import android.util.Log
import com.google.gson.GsonBuilder
import cz.lriedel.bridgex.BuildConfig
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory

class AuthenticationService(context: Context) {
    private val sharedPreferences: SharedPreferences = EncryptedSharedPreferences.create(
            AUTHENTICATION_PREFERENCES_NAME, MasterKeys.getOrCreate(MasterKeys.AES256_GCM_SPEC),
            context, EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
            EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM)

    private val authenticationClient: AuthenticationClient = Retrofit.Builder()
        .baseUrl(BuildConfig.IAM_BASE_URL)
        .addConverterFactory(GsonConverterFactory.create(GsonBuilder().setLenient().create()))
        .build()
        .create(AuthenticationClient::class.java)

    private var cachedAccessToken: String? = null
    private var cachedAccessTokenExpiration: Long = 0L

    suspend fun login(username: String?, password: String?) {
        Log.d(AuthenticationService::class.java.simpleName, "Logging in as $username...")
        val accessToken = authenticationClient.createAccessToken(BuildConfig.IAM_BASE_URL, AccessTokenRequest(username, password, null))
        setRefreshToken(accessToken.refreshToken)
        setCachedAccessToken(accessToken)
    }

    fun logout() {
        setRefreshToken(null)
        setCachedAccessToken(null)
    }

    suspend fun getAccessToken(): String? {
        if (cachedAccessToken != null && System.currentTimeMillis() < cachedAccessTokenExpiration) {
            return cachedAccessToken
        }
        else {
            Log.d(AuthenticationService::class.java.simpleName, "Received a request to obtain an access token...")
            val refreshToken = sharedPreferences.getString(REFRESH_TOKEN_KEY, null) ?: return null

            return try {
                val accessToken = authenticationClient.createAccessToken(BuildConfig.IAM_BASE_URL, AccessTokenRequest(null, null, refreshToken))
                setRefreshToken(accessToken.refreshToken)
                setCachedAccessToken(accessToken)
                accessToken.accessToken
            } catch (e: Exception) {
                Log.e(AuthenticationService::class.java.simpleName, "An error occurred when obtaining an access token.", e)
                null
            }
        }
    }

    private fun setRefreshToken(refreshToken: String?) {
        with(sharedPreferences.edit()) {
            refreshToken?.let { putString(REFRESH_TOKEN_KEY, it) } ?: remove(REFRESH_TOKEN_KEY)
            apply()
        }
    }

    private fun setCachedAccessToken(accessToken: AccessToken?) {
        if (accessToken == null) {
            cachedAccessToken = null
        }
        else {            
            cachedAccessToken = accessToken.accessToken
            cachedAccessTokenExpiration = System.currentTimeMillis() + (accessToken.validity * 1000 * ACCESS_TOKEN_VALIDITY_MULTIPLIER).toLong()
        }
    }

    companion object {
        private const val AUTHENTICATION_PREFERENCES_NAME = "AuthenticationPreferences"
        private const val REFRESH_TOKEN_KEY = "RefreshToken"
        private const val ACCESS_TOKEN_VALIDITY_MULTIPLIER = 0.95
    }
}
