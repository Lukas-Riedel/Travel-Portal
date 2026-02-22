package cz.lriedel.bridgex.notification

data class Notification(
    val title: String,
    val body: String,
    val intentExtras: Map<String, Any>
)