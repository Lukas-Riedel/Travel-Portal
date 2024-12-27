import SynchronizationHandlerConfiguration from "./SynchronizationHandlerConfiguration";

export default interface SynchronizationHandler {
    synchronize(args: any, configuration: SynchronizationHandlerConfiguration): Promise<void>;
}