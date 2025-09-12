<?php
    namespace Core\OpenLineage;

    use Core\Service\Authentication\AuthenticationService;

    class IbmCloudOpenLineageEventPublisher implements OpenLineageEventPublisher {

        private const IBM_CREATE_OPENLINEAGE_EVENT_URL = "https://api.dataplatform.dev.cloud.ibm.com/gov_lineage/v2/lineage_events/openlineage";

        private readonly AuthenticationService $authenticationService;
        private readonly \HttpClient $httpClient;

        public function __construct(AuthenticationService $authenticationService, \HttpClient $httpClient) {
            $this->authenticationService = $authenticationService;
            $this->httpClient = $httpClient;
        }

        public function publishEvent(OpenLineageEvent $event) : void {
            $this->httpClient->executeRequest(\HttpMethod::POST, self::IBM_CREATE_OPENLINEAGE_EVENT_URL, array("Content-Type: application/json", 
                "Authorization: Bearer " . $this->authenticationService->getIbmCloudAccessToken()), json_encode($event)); 
        }
    }
?>