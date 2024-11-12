package cz.lriedel.photo.uploader;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.boot.web.client.RestTemplateBuilder;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.stereotype.Component;
import org.springframework.web.client.RestTemplate;

import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;

import cz.lriedel.photo.uploader.model.AccessToken;
import cz.lriedel.photo.uploader.model.request.AccessTokenPrototype;

@Component
public class AccessTokenProvider {

    private static final String IAM_ENDPOINT = "/iam";

    private final RestTemplate restTemplate;
    private final ObjectMapper objectMapper;
    private final String apiKey;

    private String accessToken;
    private long expiration;

    public AccessTokenProvider(ObjectMapper objectMapper, @Value("${service.url}") String serviceUrl, @Value("${api.key}") String apiKey) {
        this.restTemplate = new RestTemplateBuilder()
            .rootUri(serviceUrl)
            .defaultHeader(HttpHeaders.CONTENT_TYPE, MediaType.APPLICATION_JSON_VALUE)
            .build();
        this.objectMapper = objectMapper;
        this.apiKey = apiKey;
    }

    public String getAccessToken() throws JsonProcessingException {
        if (expiration < System.currentTimeMillis() / 1000) {
            AccessTokenPrototype request = new AccessTokenPrototype(apiKey);
            AccessToken response = restTemplate.postForObject(IAM_ENDPOINT, objectMapper.writeValueAsString(request), AccessToken.class);
            this.accessToken = response.accessToken();
            this.expiration = System.currentTimeMillis() / 1000 + response.validity() / 2;
        }

        return accessToken;
    }
}