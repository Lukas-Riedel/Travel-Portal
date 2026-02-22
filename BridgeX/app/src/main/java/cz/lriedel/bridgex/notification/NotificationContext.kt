package cz.lriedel.bridgex.notification

object NotificationContext {
    val headers = ThreadLocal<Map<String, Any>>()
}