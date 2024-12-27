export default class SynchronizationHandlerConfiguration {
    baseUrl: string;
    requestConfig: any;
    configuration: any;

    constructor(baseUrl: string, requestConfig: any, configuration: any) {
        this.baseUrl = baseUrl;
        this.requestConfig = requestConfig;
        this.configuration = configuration;
    }
}