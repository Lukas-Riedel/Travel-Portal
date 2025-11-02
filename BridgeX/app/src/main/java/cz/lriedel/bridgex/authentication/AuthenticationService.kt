package cz.lriedel.bridgex.authentication

import android.content.Context
import android.content.SharedPreferences
import android.util.Log
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKeys
import cz.lriedel.bridgex.IamClient
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock

class AuthenticationService private constructor(context: Context) {
    private val sharedPreferences: SharedPreferences = EncryptedSharedPreferences.create(
            AUTHENTICATION_PREFERENCES_NAME, MasterKeys.getOrCreate(MasterKeys.AES256_GCM_SPEC),
            context.applicationContext, EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
            EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM)

    private val iamClient: IamClient = IamClient.getOrCreate()

    private val tokenMutex = Mutex()

    private var cachedAccessToken: String? = null
    private var cachedAccessTokenExpiration: Long = 0L

    suspend fun login(username: String?, password: String?) {
        Log.d(AuthenticationService::class.java.simpleName, "Logging in as $username...")
        val iamResponse = iamClient.createToken(TokenRequest(username, password, null, DEFAULT_TOKEN_SCOPE))
        extractIamResponse(iamResponse)
    }

    fun logout() {
        extractIamResponse(null)
    }

    suspend fun getAccessToken(): String? {
        if (cachedAccessToken != null && System.currentTimeMillis() < cachedAccessTokenExpiration) {
            return cachedAccessToken
        }

        return tokenMutex.withLock {
            if (cachedAccessToken != null && System.currentTimeMillis() < cachedAccessTokenExpiration) {
                return@withLock cachedAccessToken
            }

            Log.d(AuthenticationService::class.java.simpleName, "Received a request to obtain an access token...")
            
            val refreshToken = sharedPreferences.getString(REFRESH_TOKEN_KEY, null) ?: return@withLock null

            return@withLock try {
                val iamResponse = iamClient.createToken(TokenRequest(null, null, refreshToken, DEFAULT_TOKEN_SCOPE))
                extractIamResponse(iamResponse)
                iamResponse.accessToken
            } catch (e: Exception) {
                Log.e(AuthenticationService::class.java.simpleName, "An error occurred when obtaining an access token.", e)
                null
            }
        }
    }

    private fun extractIamResponse(iamResponse: IamResponse?) {
        if (iamResponse == null) {
            cachedAccessToken = null
        }
        else {            
            cachedAccessToken = iamResponse.accessToken
            cachedAccessTokenExpiration = System.currentTimeMillis() + (iamResponse.expiresIn * 1000 * ACCESS_TOKEN_VALIDITY_MULTIPLIER).toLong()
            
            with(sharedPreferences.edit()) {
                iamResponse.refreshToken?.let { putString(REFRESH_TOKEN_KEY, it) } ?: remove(REFRESH_TOKEN_KEY)
                apply()
            }
        }
    }

    companion object {
        private const val AUTHENTICATION_PREFERENCES_NAME = "AuthenticationPreferences"
        private const val REFRESH_TOKEN_KEY = "RefreshToken"
        private const val DEFAULT_TOKEN_SCOPE = "openid offline_access"
        private const val ACCESS_TOKEN_VALIDITY_MULTIPLIER = 0.95

        @Volatile
        private var INSTANCE: AuthenticationService? = null

        fun getOrCreate(context: Context): AuthenticationService {
            return INSTANCE ?: synchronized(this) {
                val instance = INSTANCE ?: AuthenticationService(context.applicationContext)
                INSTANCE = instance
                instance
            }
        }
    }
}
