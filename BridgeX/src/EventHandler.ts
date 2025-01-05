import EventHandlerConfiguration from "./EventHandlerConfiguration";

export default interface EventHandler {
    handle(args: any, configuration: EventHandlerConfiguration): Promise<void>;
}