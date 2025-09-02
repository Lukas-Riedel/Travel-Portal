package cz.lriedel.bridgex

import com.google.gson.GsonBuilder
import cz.lriedel.bridgex.authentication.AuthenticationService
import cz.lriedel.bridgex.device.DeviceRequest
import cz.lriedel.bridgex.fitness.FitnessRequest
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import retrofit2.http.Body
import retrofit2.http.POST
import retrofit2.http.PUT
import retrofit2.http.Path

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

    companion object {
        @JvmStatic
        fun create(authenticationService: AuthenticationService): CoreClient {
            val client = OkHttpClient.Builder()
                .addInterceptor { chain: Interceptor.Chain ->
                    val accessToken = kotlinx.coroutines.runBlocking {
                        authenticationService.getAccessToken()
                    }
                    val request = chain.request().newBuilder()
                        .addHeader("Authorization", "Bearer $accessToken")
                        .build()
                    chain.proceed(request)
                }.build()

            return Retrofit.Builder()
                .baseUrl(BuildConfig.CORE_BASE_URL)
                .addConverterFactory(GsonConverterFactory.create(GsonBuilder().setLenient().create()))
                .client(client)
                .build()
                .create(CoreClient::class.java)
        }
    }
}
