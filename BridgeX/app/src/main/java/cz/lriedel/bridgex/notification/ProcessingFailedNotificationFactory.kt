package cz.lriedel.bridgex.notification

import android.content.Context
import cz.lriedel.bridgex.R

class ProcessingFailedNotificationFactory(
    private val context: Context
) : NotificationFactory {
    override suspend fun create(args: Map<String, Any>): Notification? {
        val name = args["name"] as? String ?: return null
        val innerArgs = args["args"] as? Map<*, *> ?: return null

        return when (name) {
            PHOTOS_UPLOADING_TRIGGERED_EVENT_NAME -> {
                val placeId = innerArgs["placeId"] as? String ?: return null
                val placeName = innerArgs["placeId"] as? String ?: return null
                Notification(
                    context.getString(R.string.title_photos_not_uploaded),
                    context.getString(R.string.message_photos_not_uploaded, placeName),
                    mapOf<String, Any>("placeId" to placeId)
                )
            }
            PHOTO_REPLACING_TRIGGERED_EVENT_NAME -> {
                val placeId = innerArgs["placeId"] as? String ?: return null
                val placeName = innerArgs["placeId"] as? String ?: return null
                Notification(
                    context.getString(R.string.title_photo_not_replaced),
                    context.getString(R.string.message_photo_not_replaced, placeName),
                    mapOf<String, Any>("placeId" to placeId)
                )
            }
            HIGHLIGHTS_SELECTING_TRIGGERED_EVENT_NAME -> createHighlightsSelectingTriggeredNotification(innerArgs)
            else -> null
        }
    }

    private fun createHighlightsSelectingTriggeredNotification(args: Map<*, *>): Notification? {
        val title = context.getString(R.string.title_highlights_not_created)
        val type = args["highlightType"] as? String ?: return null
        val entityName = args["entityName"] as? String ?: return null
        val entityId = args["entityId"] as? String ?: return null
        
        return when (type) {
            "place" -> Notification(
                title,
                context.getString(R.string.message_highlights_not_created_place, entityName),
                mapOf<String, Any>("placeId" to entityId)
            )
            "trip" -> Notification(
                title,
                context.getString(R.string.message_highlights_not_created_trip, entityName),
                mapOf<String, Any>("tripId" to entityId)
            )
            "category" -> Notification(
                title,
                context.getString(R.string.message_highlights_not_created_category, entityName),
                mapOf<String, Any>("categoryId" to entityId)
            )
            "year" -> Notification(
                title,
                context.getString(R.string.message_highlights_not_created_year, entityName),
                mapOf<String, Any>("year" to entityId)
            )
            else -> null
        }
    }

    companion object {
        private const val PHOTOS_UPLOADING_TRIGGERED_EVENT_NAME = "PhotosUploadingTriggered"
        private const val PHOTO_REPLACING_TRIGGERED_EVENT_NAME = "PhotoReplacingTriggered"
        private const val HIGHLIGHTS_SELECTING_TRIGGERED_EVENT_NAME = "HighlightsSelectingTriggered"
    }
}
