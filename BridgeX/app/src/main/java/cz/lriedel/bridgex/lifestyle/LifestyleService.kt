package cz.lriedel.bridgex.lifestyle

import android.content.Context
import android.util.Log
import androidx.health.connect.client.HealthConnectClient
import androidx.health.connect.client.aggregate.AggregateMetric
import androidx.health.connect.client.changes.UpsertionChange
import androidx.health.connect.client.records.StepsRecord.Companion.COUNT_TOTAL
import androidx.health.connect.client.records.WeightRecord
import androidx.health.connect.client.records.metadata.DataOrigin
import androidx.health.connect.client.request.AggregateRequest
import androidx.health.connect.client.request.ChangesTokenRequest
import androidx.health.connect.client.request.ReadRecordsRequest
import androidx.health.connect.client.time.TimeRangeFilter
import cz.lriedel.bridgex.CoreClient
import cz.lriedel.bridgex.authentication.AuthenticationService
import kotlinx.coroutines.delay
import java.math.BigDecimal
import java.math.RoundingMode
import java.time.Instant
import java.time.LocalDate

class LifestyleService private constructor(
    context: Context,
    authenticationService: AuthenticationService
) {
    private val healthClient = HealthConnectClient.getOrCreate(context)
    private val coreClient = CoreClient.getOrCreate(authenticationService)
    private val preferences = context.getSharedPreferences(PREFERENCES_NAME, Context.MODE_PRIVATE)

    suspend fun updateLifestyle(start: Long, end: Long, height: Long, birthYear: Long, male: Boolean) {
        Log.i(LifestyleService::class.java.simpleName, "Updating lifestyle for an interval ($start-$end)...")

        var currentWaitTime = WAIT_TIME_MILLISECONDS.toLong()
        repeat(MAX_RETRIES) {
            try {
                doUpdateLifestyle(start, end, height, birthYear, male)
                return
            }
            catch (e: Exception) {
                Log.e(LifestyleService::class.java.simpleName, "An error occurred when updating lifestyle for an interval ($start-$end). Retrying...", e)
            }

            delay(currentWaitTime)
            currentWaitTime *= BACK_OFF_MULTIPLIER.toLong()
        }
    }

    private suspend fun doUpdateLifestyle(start: Long, end: Long, height: Long, birthYear: Long, male: Boolean) {
        val timeRange = TimeRangeFilter.between(Instant.ofEpochSecond(start), Instant.ofEpochSecond(end))
        val dataOriginFilter: Set<DataOrigin> = setOf(DataOrigin(GOOGLE_FITNESS_DATA_ORIGIN))

        // Fetch latest weight before this interval — used in both Step 1 and Step 2.
        val weightBeforeStart = healthClient.readRecords(
            ReadRecordsRequest(
                recordType = WeightRecord::class,
                timeRangeFilter = TimeRangeFilter.before(Instant.ofEpochSecond(start)),
                ascendingOrder = false,
                pageSize = 1
            )
        ).records.firstOrNull()

        val weightKg = weightBeforeStart?.weight?.inKilograms ?: 0.0
        val weightContent = BigDecimal.valueOf(weightKg)
            .setScale(1, RoundingMode.HALF_UP)
            .toPlainString()

        // Step 1: Record calories burnt by steps.
        val metrics: Set<AggregateMetric<*>> = setOf(COUNT_TOTAL)

        val aggregationRequest = AggregateRequest(metrics, timeRange, dataOriginFilter)
        val aggregationResult = healthClient.aggregate(aggregationRequest)

        val steps = aggregationResult[COUNT_TOTAL] ?: 0L
        val caloriesBurntBySteps = if (steps > 0 && weightKg > 0.0) {
            val strideLengthKm = (height / 100.0) * 0.414 / 1000.0
            val distanceKm = steps * strideLengthKm
            (distanceKm * weightKg * 0.57).toLong()
        } else {
            0L
        }

        val stepCaloriesRequest = LifestyleRequest(
            timestamp = start,
            type = "steps",
            content = steps.toString(),
            calories = caloriesBurntBySteps
        )

        try {
            coreClient.recordLifestyleEvent(stepCaloriesRequest)
        } catch (e: Exception) {
            Log.e(LifestyleService::class.java.simpleName, "Failed to record step calories for interval ($start-$end), continuing...", e)
        }

        // Step 2: Record basal metabolism calories.
        val age = LocalDate.now().year - birthYear.toInt()
        // Mifflin-St Jeor formula (kcal/day), prorated to the length of the interval
        val sexOffset = if (male) 5.0 else -161.0
        val bmrPerDay = (10.0 * weightKg) + (6.25 * height) - (5.0 * age) + sexOffset
        val bmr = bmrPerDay * ((end - start) / 86400.0)

        val basalMetabolismRequest = LifestyleRequest(
            timestamp = start,
            type = "basalMetabolism",
            content = weightContent,
            calories = bmr.toLong()
        )

        try {
            coreClient.recordLifestyleEvent(basalMetabolismRequest)
        } catch (e: Exception) {
            Log.e(LifestyleService::class.java.simpleName, "Failed to record basal metabolism for interval ($start-$end), continuing...", e)
        }

        // Record 3: Record current weight.
        // TODO: Do not fetch this for each interval - do it only once per push notification.
        var token = preferences.getString(WEIGHT_CHANGES_TOKEN_KEY, null)
            ?: healthClient.getChangesToken(ChangesTokenRequest(setOf(WeightRecord::class)))
        var response = healthClient.getChanges(token)

        while (true) {
            for (change in response.changes) {
                if (change is UpsertionChange) {
                    val record = change.record as? WeightRecord ?: continue
                    val weightRequest = LifestyleRequest(
                        timestamp = record.time.epochSecond,
                        type = "weight",
                        content = BigDecimal.valueOf(record.weight.inKilograms)
                            .setScale(1, RoundingMode.HALF_UP)
                            .toPlainString()
                    )
                    try {
                        coreClient.recordLifestyleEvent(weightRequest)
                    } catch (e: Exception) {
                        Log.e(LifestyleService::class.java.simpleName, "Failed to record weight at ${record.time.epochSecond}, continuing...", e)
                    }
                }
            }

            token = response.nextChangesToken
            if (!response.hasMore) {
                break
            }

            response = healthClient.getChanges(token)
        }

        preferences.edit().putString(WEIGHT_CHANGES_TOKEN_KEY, token).commit()
    }

    companion object {
        private const val PREFERENCES_NAME = "LifestylePreferences"
        private const val WEIGHT_CHANGES_TOKEN_KEY = "weightChangesToken"
        private const val GOOGLE_FITNESS_DATA_ORIGIN = "com.google.android.apps.fitness"
        private const val MAX_RETRIES = 3
        private const val BACK_OFF_MULTIPLIER = 2
        private const val WAIT_TIME_MILLISECONDS = 500

        @Volatile
        private var INSTANCE: LifestyleService? = null

        fun getOrCreate(context: Context, authenticationService: AuthenticationService): LifestyleService {
            return INSTANCE ?: synchronized(this) {
                val instance = INSTANCE ?: LifestyleService(context.applicationContext, authenticationService)
                INSTANCE = instance
                instance
            }
        }
    }
}
