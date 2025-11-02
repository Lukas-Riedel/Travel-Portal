package cz.lriedel.bridgex

import com.google.gson.GsonBuilder
import cz.lriedel.bridgex.authentication.IamResponse
import cz.lriedel.bridgex.authentication.TokenRequest
import okhttp3.OkHttpClient
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import retrofit2.http.Body
import retrofit2.http.POST

interface IamClient {
    @POST("token")
    suspend fun createToken(@Body tokenRequest: TokenRequest): IamResponse

    companion object {
        @Volatile
        private var INSTANCE: IamClient? = null

        fun getOrCreate(): IamClient {
            return INSTANCE ?: synchronized(this) {
                val instance = INSTANCE ?: create()
                INSTANCE = instance
                instance
            }
        }

        private fun create(): IamClient {
            val client = OkHttpClient.Builder().build()

            return Retrofit.Builder()
                .baseUrl(BuildConfig.IAM_BASE_URL)
                .addConverterFactory(GsonConverterFactory.create(GsonBuilder().setLenient().create()))
                .client(client)
                .build()
                .create(IamClient::class.java)
        }
    }
}
