package cz.lriedel.bridgex.lifestyle

data class LifestyleRequest(
    val timestamp: Long,
    val type: String,
    val content: String? = null,
    val calories: Long? = null
)
