package cz.lriedel.bridgex.authentication

data class AccessToken(
    val accessToken: String,
    val refreshToken: String,
    val validity: Long
)
