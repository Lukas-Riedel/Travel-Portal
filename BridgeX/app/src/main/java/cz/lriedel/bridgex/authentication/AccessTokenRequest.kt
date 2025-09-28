package cz.lriedel.bridgex.authentication

data class AccessTokenRequest(
    val username: String?,
    val password: String?,
    val refreshToken: String?
)
