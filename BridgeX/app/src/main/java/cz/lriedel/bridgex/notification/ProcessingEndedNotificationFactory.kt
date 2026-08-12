package cz.lriedel.bridgex.notification

import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.util.Log
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.net.HttpURLConnection
import java.net.URL
import android.content.Context
import cz.lriedel.bridgex.R

class ProcessingEndedNotificationFactory(
    private val context: Context
) : NotificationFactory {
    override suspend fun create(args: Map<String, Any>): Notification? {
        val name = args["name"] as? String ?: return null
        val innerArgs = args["args"] as? Map<*, *> ?: return null

        return when (name) {
            PHOTOS_UPLOADING_TRIGGERED_EVENT_NAME -> {
                val sendNotification = innerArgs["sendNotification"] as? Boolean ?: false
                if (!sendNotification) {
                    return null
                }

                val placeId = innerArgs["placeId"] as? String ?: return null
                val placeName = innerArgs["placeName"] as? String ?: return null
                val photoUrl = innerArgs["result"] as? String

                val imageBitmap = photoUrl?.let { url ->
                    fetchImageBitmap(url)
                }

                Notification(
                    context.getString(R.string.title_photos_uploaded),
                    context.getString(R.string.message_photos_uploaded, placeName),
                    mapOf<String, Any>("placeId" to placeId),
                    imageBitmap
                )
            }
            PHOTO_REPLACING_TRIGGERED_EVENT_NAME -> {
                val sendNotification = innerArgs["sendNotification"] as? Boolean ?: false
                if (!sendNotification) {
                    return null
                }

                val placeId = innerArgs["placeId"] as? String ?: return null
                val placeName = innerArgs["placeName"] as? String ?: return null
                Notification(
                    context.getString(R.string.title_photo_replaced),
                    context.getString(R.string.message_photo_replaced, placeName),
                    mapOf<String, Any>("placeId" to placeId)
                )
            }
            else -> null
        }
    }

    private suspend fun fetchImageBitmap(baseUrl: String): Bitmap? = withContext(Dispatchers.IO) {
        val formattedUrl = "$baseUrl=w1024"
        
        try {
            val connection = (URL(formattedUrl).openConnection() as HttpURLConnection).apply {
                doInput = true
                connectTimeout = 3000
                readTimeout = 3000
                connect()
            }

            if (connection.responseCode == HttpURLConnection.HTTP_OK) {
                connection.inputStream.use { inputStream ->
                    BitmapFactory.decodeStream(inputStream)
                }
            }
            else {
                null
            }
        }
        catch (e: Exception) {
            Log.e(ProcessingEndedNotificationFactory::class.java.simpleName, "Failed to fetch notification image from $formattedUrl", e)
            null
        }
    }

    companion object {
        private const val PHOTOS_UPLOADING_TRIGGERED_EVENT_NAME = "PhotosUploadingTriggered"
        private const val PHOTO_REPLACING_TRIGGERED_EVENT_NAME = "PhotoReplacingTriggered"
    }
}
