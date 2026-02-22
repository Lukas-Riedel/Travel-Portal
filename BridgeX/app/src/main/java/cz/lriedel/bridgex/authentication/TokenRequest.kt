package cz.lriedel.bridgex.authentication

data class TokenRequest(
    val username: String?,
    val password: String?,
    val refreshToken: String?,
    val scope: String?
)
