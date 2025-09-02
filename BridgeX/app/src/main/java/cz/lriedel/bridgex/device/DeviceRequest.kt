package cz.lriedel.bridgex.device

data class DeviceRequest(
    val type: String,
    val name: String,
    val token: String
)
