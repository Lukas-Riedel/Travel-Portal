package cz.lriedel.bridgex.device

data class DeviceRequest(
    val id: String,
    val type: String,
    val name: String,
    val `data`: DeviceData
)
