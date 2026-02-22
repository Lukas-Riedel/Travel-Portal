package cz.lriedel.bridgex

import com.google.gson.GsonBuilder
import cz.lriedel.bridgex.authentication.AuthenticationService
import cz.lriedel.bridgex.device.DeviceRequest
import cz.lriedel.bridgex.fitness.FitnessRequest
import cz.lriedel.bridgex.geocoding.AddressResponse
import cz.lriedel.bridgex.notification.NotificationContext
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.Request
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.PUT
import retrofit2.http.Path
import retrofit2.http.Query

interface CoreClient {
    @POST("devices")
    suspend fun createDevice(
        @Body deviceRequest: DeviceRequest
    )

    @PUT("fitness/{timestamp}")
    suspend fun replaceFitness(
        @Path("timestamp") timestamp: Long,
        @Body fitnessRequest: FitnessRequest
    )

    @GET("address")
    suspend fun getAddress(
        @Query("latitude") latitude: Double,
        @Query("longitude") longitude: Double
    ): AddressResponse

    companion object {
        @Volatile
        private var INSTANCE: CoreClient? = null

        fun getOrCreate(authenticationService: AuthenticationService): CoreClient {
            return INSTANCE ?: synchronized(this) {
                val instance = INSTANCE ?: create(authenticationService)
                INSTANCE = instance
                instance
            }
        }

        private fun create(authenticationService: AuthenticationService): CoreClient {
            val client = OkHttpClient.Builder()
                .addInterceptor { chain: Interceptor.Chain ->
                    val accessToken = kotlinx.coroutines.runBlocking {
                        authenticationService.getAccessToken()
                    }
                    val request = chain.request()
                    val requestBuilder = request.newBuilder()
                    (request.tag(Map::class.java))?.forEach { (key, value) ->
                        requestBuilder.addHeader(key.toString(), value.toString())
                    }
                    val newRequest = requestBuilder
                        .addHeader("Authorization", "Bearer $accessToken")
                        .build()
                    chain.proceed(newRequest)
                }.build()

            return Retrofit.Builder()
                .baseUrl(BuildConfig.CORE_BASE_URL)
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
                .create(CoreClient::class.java)
        }
    }
}
