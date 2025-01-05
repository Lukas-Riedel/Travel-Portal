export default class SynchronizationHandlerConfiguration {
    baseUrl: string;
    requestConfig: any;

    constructor(baseUrl: string, requestConfig: any) {
        this.baseUrl = baseUrl;
        this.requestConfig = requestConfig;
    }
}