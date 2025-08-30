import AsyncStorage from "@react-native-async-storage/async-storage";
import axios from "axios";
import Logger from "./Logger";
import EventHandler from "./EventHandler";
import { FitnessActivityDetectedEventHandler } from "./FitnessActivityDetectedEventHandler";
import EventHandlerConfiguration from "./EventHandlerConfiguration";

export default class Synchronizer {

    _logger: Logger;
    _handlers: Map<string, EventHandler>;

    constructor(logger: Logger) {
        this._logger = logger;
        this._handlers = new Map<string, EventHandler>();
        this._handlers.set("FitnessActivityDetected", new FitnessActivityDetectedEventHandler());
    }

    async synchronize(): Promise<void> {
        const nextSync = await AsyncStorage.getItem("nextSync");
        if (nextSync === null || new Date(Number(nextSync)) < new Date()) {
            await this.doSynchronize();
        }
    }

    async doSynchronize(): Promise<void> {
        try {
            const baseUrl = await AsyncStorage.getItem("baseUrl");
            if (baseUrl === null) {
                throw new Error("The Service URL is not set");
            }
            
            const apiKey = await AsyncStorage.getItem("apiKey");
            if (apiKey === null) {
                throw new Error("The API key is not set");
            }

            const accessToken = (await axios.post(`${baseUrl}/iam`, {
                "apiKey": apiKey
            })).data.accessToken;

            const requestConfig = {
                headers: {
                    "Authorization": `Bearer ${accessToken}`
                }
            };
            
            const handlerConfiguration = new EventHandlerConfiguration(baseUrl, requestConfig);

            for (const [name, handler] of this._handlers.entries()) {
                const events = (await axios.get(`${baseUrl}/events?name=${name}`, requestConfig)).data;
        
                this._logger.logInfo("The synchronization has started", `There are ${events.length} items in the queue`);    

                for (const event of events) {
                    try {
                        await handler.handle(event.args, handlerConfiguration);
                    }
                    catch (e) {
                        this._logger.logError("There was an error processing the item", (e as Error).message);
                    }
                    finally {
                        await axios.delete(`${baseUrl}/events/${event.id}`, requestConfig);
                    }
                }
            }

            this._logger.logInfo("The synchronization has been completed", "");

            const lastSync = new Date();
            await AsyncStorage.setItem("lastSync", String(lastSync.getTime()));
    
            // TODO: Define synchronization interval.
            const nextSync = new Date(lastSync.getTime() + 1800 * 1000);

            await AsyncStorage.setItem("nextSync", String(nextSync.getTime()));
        }
        catch (e) {
            this._logger.logError("An unexpected error happened during the synchronization", (e as Error).message);
        }
    }
}