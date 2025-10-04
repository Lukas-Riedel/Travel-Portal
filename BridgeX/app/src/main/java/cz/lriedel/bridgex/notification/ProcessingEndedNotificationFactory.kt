package cz.lriedel.bridgex.notification

import android.content.Context
import cz.lriedel.bridgex.R

class ProcessingEndedNotificationFactory(
    private val context: Context
) : NotificationFactory {
    override suspend fun create(args: Map<String, Any>): Notification? {
        val name = args["name"] as? String ?: return null
        val innerArgs = args["args"] as? Map<*, *> ?: return null
        val placeName = innerArgs["placeName"] as? String ?: return null
        val placeId = innerArgs["placeId"] as? String ?: return null

        return when (name) {
            PHOTOS_UPLOADING_TRIGGERED_EVENT_NAME -> Notification(
                context.getString(R.string.title_photos_uploaded),
                context.getString(R.string.message_photos_uploaded, placeName),
                mapOf("placeId" to placeId)
            )
            PHOTO_REPLACING_TRIGGERED_EVENT_NAME -> Notification(
                context.getString(R.string.title_photo_replaced),
                context.getString(R.string.message_photo_replaced, placeName),
                mapOf("placeId" to placeId)
            )
            else -> null
        }
    }

    companion object {
        private const val PHOTOS_UPLOADING_TRIGGERED_EVENT_NAME = "PhotosUploadingTriggered"
        private const val PHOTO_REPLACING_TRIGGERED_EVENT_NAME = "PhotoReplacingTriggered"
    }
}
