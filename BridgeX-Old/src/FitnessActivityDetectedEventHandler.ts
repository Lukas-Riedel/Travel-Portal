import axios from "axios";
import {
    AggregateResultRecordType,
    AggregateResult,
    RecordType,
    ReadRecordsResult,
    aggregateRecord,
    readRecords
} from "react-native-health-connect";
import EventHandler from "./EventHandler";
import EventHandlerConfiguration from "./EventHandlerConfiguration";

export class FitnessActivityDetectedEventHandler implements EventHandler {
    async handle(args: any, configuration: EventHandlerConfiguration): Promise<void> {        
        await axios.put(`${configuration.baseUrl}/fitness/${args.start}`, {
            "steps": await this._getTotalSteps(args.start, args.end),
            "distance": await this._getTotalDistance(args.start, args.end),
            "seconds": await this._getTotalTimeInMotion(args.start, args.end),
            "calories": await this._getTotalCalories(args.start, args.end)
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
    
        return Math.round(totalTimeInMotion / 1000);
    };

    async _getAggregatedFitnessData<T extends AggregateResultRecordType>(recordType: T, start: number, end: number): Promise<AggregateResult<T>>  {
        const maxRetries = 10000;

        let attempt = 0;
    
        // TODO: Find a better mechanism.
        while (attempt < maxRetries) {
            try {
                return await aggregateRecord({
                    recordType: recordType,
                    timeRangeFilter: {
                        operator: "between",
                        startTime: new Date(start * 1000).toISOString(),
                        endTime: new Date(end * 1000).toISOString()
                    }
                });
            } catch (error) {
                ++attempt;
                console.warn(`Rate limit exceeded. Retrying... (Attempt ${attempt})`);
            }
        }
    
        throw new Error("Max retries reached. Unable to fetch aggregated fitness data.");
    };
    
    async _getFitnessData<T extends RecordType>(recordType: T, start: number, end: number): Promise<ReadRecordsResult<T>> {
        const maxRetries = 10000;

        let attempt = 0;
    
        // TODO: Find a better mechanism.
        while (attempt < maxRetries) {
            try {
                return await readRecords(recordType, {
                    timeRangeFilter: {
                        operator: "between",
                        startTime: new Date(start * 1000).toISOString(),
                        endTime: new Date(end * 1000).toISOString()
                    }
                });
            } catch (error) {
                ++attempt;
                console.warn(`Rate limit exceeded. Retrying... (Attempt ${attempt})`);
            }
        }
    
        throw new Error("Max retries reached. Unable to fetch aggregated fitness data.");
    };
}