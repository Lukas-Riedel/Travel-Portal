package cz.lriedel.bridgex.authentication

import android.content.Context
import android.content.SharedPreferences
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKeys
import android.util.Log
import com.google.gson.GsonBuilder
import cz.lriedel.bridgex.BuildConfig
import retrofit2.Retrofit
import cz.lriedel.bridgex.IamClient
import cz.lriedel.bridgex.IamClient.Companion.create
import retrofit2.converter.gson.GsonConverterFactory

class AuthenticationService(context: Context) {
    private val sharedPreferences: SharedPreferences = EncryptedSharedPreferences.create(
            AUTHENTICATION_PREFERENCES_NAME, MasterKeys.getOrCreate(MasterKeys.AES256_GCM_SPEC),
            context, EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
            EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM)

    private val iamClient: IamClient = create()

    private var cachedAccessToken: String? = null
    private var cachedAccessTokenExpiration: Long = 0L

    suspend fun login(username: String?, password: String?) {
        Log.d(AuthenticationService::class.java.simpleName, "Logging in as $username...")
        val iamResponse = iamClient.createToken(TokenRequest(username, password, null, DEFAULT_TOKEN_SCOPE))
        setRefreshToken(iamResponse.refreshToken)
        cacheAccessToken(iamResponse)
    }

    fun logout() {
        setRefreshToken(null)
        cacheAccessToken(null)
    }

    suspend fun getAccessToken(): String? {
        if (cachedAccessToken != null && System.currentTimeMillis() < cachedAccessTokenExpiration) {
            return cachedAccessToken
        }
        else {
            Log.d(AuthenticationService::class.java.simpleName, "Received a request to obtain an access token...")
            val refreshToken = sharedPreferences.getString(REFRESH_TOKEN_KEY, null) ?: return null

            return try {
                val iamResponse = iamClient.createToken(TokenRequest(null, null, refreshToken, DEFAULT_TOKEN_SCOPE))
                setRefreshToken(iamResponse.refreshToken)
                cacheAccessToken(iamResponse)
                iamResponse.accessToken
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

    private fun cacheAccessToken(iamResponse: IamResponse?) {
        if (iamResponse == null) {
            cachedAccessToken = null
        }
        else {            
            cachedAccessToken = iamResponse.accessToken
            cachedAccessTokenExpiration = System.currentTimeMillis() + (iamResponse.expiresIn * 1000 * ACCESS_TOKEN_VALIDITY_MULTIPLIER).toLong()
        }
    }

    companion object {
        private const val AUTHENTICATION_PREFERENCES_NAME = "AuthenticationPreferences"
        private const val REFRESH_TOKEN_KEY = "RefreshToken"
        private const val DEFAULT_TOKEN_SCOPE = "openid offline_access"
        private const val ACCESS_TOKEN_VALIDITY_MULTIPLIER = 0.95
    }
}
