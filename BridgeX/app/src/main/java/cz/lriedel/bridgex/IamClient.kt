package cz.lriedel.bridgex

import com.google.gson.GsonBuilder
import cz.lriedel.bridgex.authentication.IamResponse
import cz.lriedel.bridgex.authentication.TokenRequest
import cz.lriedel.bridgex.device.DeviceType
import cz.lriedel.bridgex.notification.NotificationContext
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.Request
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
            val client = OkHttpClient.Builder()
                .addInterceptor { chain: Interceptor.Chain ->
                    val request = chain.request()
                    val requestBuilder = request.newBuilder()
                    (request.tag(Map::class.java))?.forEach { (key, value) ->
                        requestBuilder.addHeader(key.toString(), value.toString())
                    }
                    val newRequest = requestBuilder
                        .addHeader("Request-Origin", DeviceType.BRIDGEX.value)
                        .build()
                    chain.proceed(newRequest)
                }.build()

            return Retrofit.Builder()
                .baseUrl(BuildConfig.IAM_BASE_URL)
                .addConverterFactory(GsonConverterFactory.create(GsonBuilder().setLenient().create()))
                .callFactory { request: Request ->
                    val headers = NotificationContext.headers.get()
                    val newRequest = if (headers != null) {
                        request.newBuilder()
                            .tag(Map::class.java, headers)
                            .build()
                    } else {
                        request
                    }
                    client.newCall(newRequest)
                }
                .build()
                .create(IamClient::class.java)
        }
    }
}
