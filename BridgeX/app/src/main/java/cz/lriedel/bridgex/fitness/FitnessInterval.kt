package cz.lriedel.bridgex.fitness

import android.os.Parcelable
import kotlinx.parcelize.Parcelize

@Parcelize
data class FitnessInterval(
    val start: Long,
    val end: Long
) : Parcelable