import axios from "axios";
import {
    AggregateResultRecordType,
    AggregateResult,
    RecordType,
    ReadRecordsResult,
    aggregateRecord,
    readRecords
} from "react-native-health-connect";
import SynchronizationHandler from "./SynchronizationHandler";
import SynchronizationHandlerConfiguration from "./SynchronizationHandlerConfiguration";

export class FitnessSynchronizationHandler implements SynchronizationHandler {
    async synchronize(args: any, configuration: SynchronizationHandlerConfiguration): Promise<void> {
        const start = args.start;
        const end = args.start + configuration.configuration.fitnessRecordDuration;
        
        await axios.put(`${configuration.baseUrl}/fitness/${start}`, {
            "steps": await this._getTotalSteps(start, end),
            "distance": await this._getTotalDistance(start, end),
            "minutes": await this._getTotalTimeInMotion(start, end),
            "calories": await this._getTotalCalories(start, end)
        }, configuration.requestConfig);
    }
    
    async _getTotalSteps(start: number, end: number): Promise<number> {
        return (await this._getAggregatedFitnessData("Steps", start, end)).COUNT_TOTAL;
    };
    
    async _getTotalDistance(start: number, end: number): Promise<number> {
        return (await this._getAggregatedFitnessData("Distance", start, end)).DISTANCE.inMeters;
    };
    
    async _getTotalCalories(start: number, end: number): Promise<number> {
        return (await this._getAggregatedFitnessData("TotalCaloriesBurned", start, end)).ENERGY_TOTAL.inKilocalories;
    };
    
    async _getTotalTimeInMotion(start: number, end: number): Promise<number> {
        let totalTimeInMotion = 0;
    
        const stepRecords = (await this._getFitnessData("Steps", start, end)).records;
        for (const stepRecord of stepRecords) {
            totalTimeInMotion += (Number(new Date(stepRecord.endTime)) - Number(new Date(stepRecord.startTime)))
        }
    
        return Math.round(totalTimeInMotion / (60 * 1000));
    };

    async _getAggregatedFitnessData<T extends AggregateResultRecordType>(recordType: T, start: number, end: number): Promise<AggregateResult<T>>  {
        return await aggregateRecord({
            recordType: recordType,
            timeRangeFilter: {
                operator: "between",
                startTime: new Date(start * 1000).toISOString(),
                endTime: new Date(end * 1000).toISOString()
            }
        });
    };
    
    async _getFitnessData<T extends RecordType>(recordType: T, start: number, end: number): Promise<ReadRecordsResult<T>> {
        return await readRecords(recordType, {
            timeRangeFilter: {
                operator: "between",
                startTime: new Date(start * 1000).toISOString(),
                endTime: new Date(end * 1000).toISOString()
            }
        });
    };
}