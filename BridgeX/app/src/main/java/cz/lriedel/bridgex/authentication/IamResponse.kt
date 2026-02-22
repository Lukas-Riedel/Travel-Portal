package cz.lriedel.bridgex.authentication

data class IamResponse(
    val accessToken: String,
    val expiresIn: Long,
    val refreshToken: String,
    val refreshExpiresIn: Long
)
