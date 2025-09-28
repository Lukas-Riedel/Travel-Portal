package cz.lriedel.bridgex.authentication

// TODO: Rename to IamResponse.
data class AccessToken(
    val accessToken: String,
    val refreshToken: String,
    val validity: Long
)
