package cz.lriedel.bridgex.authentication

import com.google.gson.annotations.SerializedName

data class ClientRoles(
    @SerializedName("roles") val roles: List<String>? = null
)