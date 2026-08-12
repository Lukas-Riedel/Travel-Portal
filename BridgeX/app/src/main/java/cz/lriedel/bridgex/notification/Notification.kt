package cz.lriedel.bridgex.notification

import android.graphics.Bitmap

data class Notification(
    val title: String,
    val body: String,
    val intentExtras: Map<String, Any>,
    val image: Bitmap? = null
)