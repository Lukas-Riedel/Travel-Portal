<?php
    namespace Iam\Service\Certificate;

    use Common\Client\Http\HttpMethod;
    use Common\Client\Http\HttpClient;

    class CertificateService {

        private const JWKS_API_ENDPOINT_PATH = "/protocol/openid-connect/certs";

        private readonly HttpClient $httpClient;

        private readonly string $internalIambaseUrl;

        public function __construct(HttpClient $httpClient, string $internalIambaseUrl) {
            $this->httpClient = $httpClient;
            $this->internalIambaseUrl = $internalIambaseUrl;
        }

        public function getJwksKeys() : mixed {
            $response = $this->httpClient->executeRequest(HttpMethod::GET, $this->internalIambaseUrl . self::JWKS_API_ENDPOINT_PATH);            
            if (!is_array($response) || !isset($response["keys"]) || !is_array($response["keys"]) || count($response["keys"]) === 0) {
                throw new \RuntimeException("There is no JWKS key. Response: " . json_encode($response));
            }

            return $response;
        }
    }
?>