<?php
    namespace Iam\Service\IbmCloud;

    use Common\Client\Http\HttpMethod;
    use Common\Client\Http\HttpClient;
    use Common\Service\Authentication\IamResponse;

    class IbmCloudService {

        private const IBM_CLOUD_IAM_TOKEN_URL = "https://iam.test.cloud.ibm.com/identity/token";

        private const IBM_CLOUD_GRANT_TYPE = "urn:ibm:params:oauth:grant-type:apikey";
        private const IBM_CLOUD_RESPONSE_TYPE = "cloud_iam";

        private readonly HttpClient $httpClient;

        public function __construct(HttpClient $httpClient) {
            $this->httpClient = $httpClient;
        }

        public function getIbmCloudAccessToken() : IamResponse {
            $payload = array(
                "grant_type" => self::IBM_CLOUD_GRANT_TYPE,
                "response_type" => self::IBM_CLOUD_RESPONSE_TYPE,
                "apikey" => IBM_CLOUD_API_KEY
            );     

            $response = $this->httpClient->executeRequest(HttpMethod::POST, self::IBM_CLOUD_IAM_TOKEN_URL, 
                array("Content-Type: application/x-www-form-urlencoded"), http_build_query($payload));

            if (!isset($response["access_token"])) {
                throw new \RuntimeException("The access token could not be obtained. Response: " . json_encode($response));
            }
            
            return new IamResponse($response["access_token"], $response["expires_in"], null, null);
        }
    }
?>