package cz.lriedel.bridgex.device

data class DeviceData(
    val fcmToken: String,
    val latitude: Double?,
    val longitude: Double?,
    val address: DeviceAddressData?,
    val timezone: String?,
    val battery: Double?
)
