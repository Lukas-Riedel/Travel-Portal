package cz.lriedel.bridgex.authentication

import retrofit2.http.Body
import retrofit2.http.POST
import retrofit2.http.Url

interface AuthenticationClient {
    @POST
    suspend fun createAccessToken(
        @Url url: String,
        @Body accessTokenRequest: AccessTokenRequest
    ): AccessToken
}
