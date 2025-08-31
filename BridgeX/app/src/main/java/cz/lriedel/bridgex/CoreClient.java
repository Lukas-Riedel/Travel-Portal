package cz.lriedel.bridgex;

import com.google.gson.GsonBuilder;

import okhttp3.OkHttpClient;
import okhttp3.Request;
import retrofit2.Call;
import retrofit2.Retrofit;
import retrofit2.converter.gson.GsonConverterFactory;
import retrofit2.http.Body;
import retrofit2.http.POST;

public interface CoreClient {

    @POST("devices")
    Call<Void> createDevice(@Body DeviceRequest deviceRequest);

    static CoreClient create(AuthenticationService authenticationService) {
        OkHttpClient client = new OkHttpClient.Builder()
                .addInterceptor(chain -> {
                    String accessToken = authenticationService.getAccessToken();

                    Request request = chain.request().newBuilder()
                            .addHeader("Authorization", "Bearer " + accessToken)
                            .build();

                    return chain.proceed(request);
                }).build();

        return new Retrofit.Builder()
                .baseUrl(cz.lriedel.bridgex.BuildConfig.CORE_BASE_URL)
                .addConverterFactory(GsonConverterFactory.create(new GsonBuilder().setLenient().create()))
                .client(client)
                .build()
                .create(CoreClient.class);
    }
}
