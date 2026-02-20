<?php
    namespace Iam\Service\IbmCloud;

    use Common\Client\Http\HttpMethod;
    use Common\Client\Http\HttpClient;
    use Common\Service\Authentication\IamResponse;

    class IbmCloudService {

        private const IBM_CLOUD_IAM_TOKEN_ENDPOINT = "/identity/token";

        private const IBM_CLOUD_GRANT_TYPE = "urn:ibm:params:oauth:grant-type:apikey";
        private const IBM_CLOUD_RESPONSE_TYPE = "cloud_iam";

        private readonly HttpClient $httpClient;

        private readonly string $ibmCloudIamBaseUrl;
        private readonly string $ibmCloudApiKey;

        public function __construct(HttpClient $httpClient, string $ibmCloudIamBaseUrl, string $ibmCloudApiKey) {
            $this->httpClient = $httpClient;
            $this->ibmCloudIamBaseUrl = $ibmCloudIamBaseUrl;
            $this->ibmCloudApiKey = $ibmCloudApiKey;
        }

        public function getIbmCloudAccessToken() : IamResponse {
            $payload = array(
                "grant_type" => self::IBM_CLOUD_GRANT_TYPE,
                "response_type" => self::IBM_CLOUD_RESPONSE_TYPE,
                "apikey" => $this->ibmCloudApiKey
            );     

            $response = $this->httpClient->executeRequest(HttpMethod::POST, $this->ibmCloudIamBaseUrl . self::IBM_CLOUD_IAM_TOKEN_ENDPOINT, 
                array("Content-Type: application/x-www-form-urlencoded"), http_build_query($payload));

            if (!isset($response["access_token"])) {
                throw new \RuntimeException("The access token could not be obtained. Response: " . json_encode($response));
            }
            
            return new IamResponse($response["access_token"], $response["expires_in"], null, null);
        }
    }
?>