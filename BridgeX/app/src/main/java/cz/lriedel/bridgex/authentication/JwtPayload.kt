package cz.lriedel.bridgex.authentication

import com.google.gson.annotations.SerializedName

data class JwtPayload(
    @SerializedName("resource_access") val resourceAccess: Map<String, ClientRoles>? = null
)