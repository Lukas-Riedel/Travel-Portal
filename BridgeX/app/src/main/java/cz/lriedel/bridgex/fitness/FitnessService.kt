package cz.lriedel.bridgex.fitness

import android.content.Context
import android.util.Log
import androidx.health.connect.client.HealthConnectClient
import androidx.health.connect.client.aggregate.AggregateMetric
import androidx.health.connect.client.records.DistanceRecord.Companion.DISTANCE_TOTAL
import androidx.health.connect.client.records.StepsRecord
import androidx.health.connect.client.records.StepsRecord.Companion.COUNT_TOTAL
import androidx.health.connect.client.records.metadata.DataOrigin
import androidx.health.connect.client.request.AggregateRequest
import androidx.health.connect.client.request.ReadRecordsRequest
import androidx.health.connect.client.time.TimeRangeFilter
import cz.lriedel.bridgex.CoreClient.Companion.create
import cz.lriedel.bridgex.authentication.AuthenticationService
import kotlinx.coroutines.delay
import java.time.Instant

class FitnessService(
    context: Context,
    authenticationService: AuthenticationService
) {
    private val healthClient = HealthConnectClient.getOrCreate(context)
    private val coreClient = create(authenticationService)

    suspend fun updateFitness(start: Long, end: Long) {
        Log.i(FitnessService::class.java.simpleName, "Updating fitness for an interval ($start-$end)...")

        var currentWaitTime = WAIT_TIME_MILLISECONDS.toLong()
        repeat(MAX_RETRIES) {
            try {
                doUpdateFitness(start, end)
                return
            }
            catch (e: Exception) {
                Log.e(FitnessService::class.java.simpleName, "An error occurred when updating fitness for an interval ($start-$end). Retrying...", e)
            }

            delay(currentWaitTime)
            currentWaitTime *= BACK_OFF_MULTIPLIER.toLong()
        }
    }

    private suspend fun doUpdateFitness(start: Long, end: Long) {
        val timeRange = TimeRangeFilter.between(Instant.ofEpochSecond(start), Instant.ofEpochSecond(end))

        val metrics: Set<AggregateMetric<*>> = setOf(COUNT_TOTAL, DISTANCE_TOTAL)
        val dataOriginFilter: Set<DataOrigin> = setOf(DataOrigin("com.google.android.apps.fitness"))

        val aggregationRequest = AggregateRequest(metrics, timeRange, dataOriginFilter)
        val aggregationResult = healthClient.aggregate(aggregationRequest)

        val stepRecordsRequest = ReadRecordsRequest(
            recordType = StepsRecord::class,
            timeRangeFilter = timeRange
        );
        val stepRecordsResponse = healthClient.readRecords(stepRecordsRequest)

        val steps = aggregationResult[COUNT_TOTAL] ?: 0L
        val seconds = stepRecordsResponse.records.map {
            it.endTime.epochSecond - it.startTime.epochSecond
        }.sum()
        val distance = aggregationResult[DISTANCE_TOTAL]?.inKilometers ?: 0.0

        val fitnessRequest = FitnessRequest(steps, seconds, distance)

        coreClient.replaceFitness(start, fitnessRequest)
    }

    companion object {
        private const val MAX_RETRIES = 3
        private const val BACK_OFF_MULTIPLIER = 2
        private const val WAIT_TIME_MILLISECONDS = 500
    }
}
