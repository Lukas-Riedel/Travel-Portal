<?php
    namespace Core\OpenLineage;

    use Core\Service\Authentication\AuthenticationService;
    use Core\Service\Configuration\ConfigurationService;
    use Monolog\Logger;
    use Core\Client\Http\HttpMethod;
    use Core\Client\Http\HttpClient;

    class IbmCloudOpenLineageEventPublisher implements OpenLineageEventPublisher {

        private const IBM_CREATE_OPENLINEAGE_EVENT_URL = "https://api.dataplatform.dev.cloud.ibm.com/gov_lineage/v2/lineage_events/openlineage";

        private readonly AuthenticationService $authenticationService;

        private readonly ConfigurationService $configurationService;

        private readonly HttpClient $httpClient;

        private readonly Logger $logger;

        public function __construct(AuthenticationService $authenticationService, ConfigurationService $configurationService, 
            HttpClient $httpClient, Logger $logger) {
            $this->authenticationService = $authenticationService;
            $this->configurationService = $configurationService;
            $this->httpClient = $httpClient;
            $this->logger = $logger;
        }

        public function publishEvent(OpenLineageEvent $event) : void {
            if (!$this->configurationService->getConfigurationEntry("openLineage")["producers"]["ibmCloud"]["enabled"]) {
                return;
            }

            $response = $this->httpClient->executeRequest(HttpMethod::POST, self::IBM_CREATE_OPENLINEAGE_EVENT_URL, array("Content-Type: application/json", 
                "Authorization: Bearer " . $this->authenticationService->getIbmCloudAccessToken()), json_encode($event));

            if (isset($response["errors"]) && is_array($response["errors"])) {
                foreach ($response["errors"] as &$error) {
                    if (isset($error["message"])) {
                        $this->logger->warning("An error occurred when publishing OpenLineage event: " . $error["message"], array("error" => $error));
                    }
                }
            }
        }
    }
?>