package cz.lriedel.bridgex;

import retrofit2.Call;
import retrofit2.http.Body;
import retrofit2.http.POST;
import retrofit2.http.Url;

public interface AuthenticationClient {

    @POST
    Call<AccessToken> createAccessToken(@Url String url, @Body AccessTokenRequest accessTokenRequest);
}
