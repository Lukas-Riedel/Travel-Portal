package cz.lriedel.bridgex.lifestyle

import android.os.Parcelable
import kotlinx.parcelize.Parcelize

@Parcelize
data class LifestyleInterval(
    val start: Long,
    val end: Long,
    val height: Long,
    val birthYear: Long,
    val male: Boolean
) : Parcelable