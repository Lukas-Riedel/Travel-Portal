package cz.lriedel.agent.client;

import cz.lriedel.agent.model.Album;
import cz.lriedel.agent.model.Place;
import cz.lriedel.agent.model.request.DevicePrototype;
import cz.lriedel.agent.model.request.EventPrototype;
import cz.lriedel.agent.model.request.PhotoPrototype;
import lombok.SneakyThrows;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.core.ParameterizedTypeReference;
import org.springframework.http.HttpMethod;
import org.springframework.retry.support.RetryTemplate;
import org.springframework.stereotype.Component;
import org.springframework.web.client.RestTemplate;

import java.net.InetAddress;
import java.util.Base64;
import java.util.List;
import java.util.Map;
import java.util.Objects;

import static cz.lriedel.agent.AgentApplicationConfiguration.CORE_SERVICE_QUALIFIER;

@Component
public final class ServiceClient {

    private final RestTemplate restTemplate;
    private final RetryTemplate retryTemplate;
    private final HttpEntityProvider httpEntityProvider;

    public ServiceClient(@Qualifier(CORE_SERVICE_QUALIFIER) RestTemplate restTemplate, RetryTemplate retryTemplate,
                         HttpEntityProvider httpEntityProvider) {
        this.restTemplate = restTemplate;
        this.retryTemplate = retryTemplate;
        this.httpEntityProvider = httpEntityProvider;
    }

    public List<Place> getPlaces() {
        return retryTemplate.execute(context -> Objects.requireNonNull(restTemplate.exchange("/places?include=dates",
            HttpMethod.GET, httpEntityProvider.getEmptyHttpEntity(), new ParameterizedTypeReference<List<Place>>() {}).getBody()));
    }

    @SneakyThrows
    public void registerDevice(String deviceId, Map<String, Object> data) {
        DevicePrototype devicePrototype = new DevicePrototype(deviceId, "agent", InetAddress.getLocalHost().getHostName(), data);
        retryTemplate.execute(context -> restTemplate.postForObject(
            "/devices", httpEntityProvider.getHttpEntity(devicePrototype), Void.class));
    }

    public Album createAlbum(String placeId, long timestamp) {
        return Objects.requireNonNull(restTemplate.postForObject(
                "/places/" + placeId + "/albums?timestamp=" + timestamp,
                httpEntityProvider.getEmptyHttpEntity(), Album.class));
    }

    public void uploadPhoto(String placeId, String albumId, String fileName, String replacedPhotoId, byte[] data) {
        PhotoPrototype photoPrototype = new PhotoPrototype(fileName, replacedPhotoId, Base64.getEncoder().encodeToString(data));
        retryTemplate.execute(context -> restTemplate.postForObject(
                "/places/" + placeId + "/albums/" + albumId + "/photos",
                httpEntityProvider.getHttpEntity(photoPrototype), Void.class));
    }

    public void uploadPhoto(String placeId, String albumId, String fileName, String batchId, int expectedBatchSize, int batchPosition, byte[] data) {
        PhotoPrototype photoPrototype = new PhotoPrototype(fileName, batchId, expectedBatchSize, batchPosition, Base64.getEncoder().encodeToString(data));
        retryTemplate.execute(context -> restTemplate.postForObject(
                "/places/" + placeId + "/albums/" + albumId + "/photos",
                httpEntityProvider.getHttpEntity(photoPrototype), Void.class));
    }

    public Album refreshAlbum(String placeId, String albumId) {
        return retryTemplate.execute(context -> Objects.requireNonNull(restTemplate.postForObject(
                "/places/" + placeId + "/albums/" + albumId + "/refresh",
                httpEntityProvider.getEmptyHttpEntity(), Album.class)));
    }

    public Album refreshAlbum(String placeId, String albumId, int mainPhotoPosition) {
        return retryTemplate.execute(context -> Objects.requireNonNull(restTemplate.postForObject(
                "/places/" + placeId + "/albums/" + albumId + "/refresh?mainPhotoPosition=" + mainPhotoPosition,
                httpEntityProvider.getEmptyHttpEntity(), Album.class)));
    }

    public void createEvent(String name, Map<String, Object> args) {
        EventPrototype eventPrototype = new EventPrototype(name, args);
        restTemplate.postForObject("/events", httpEntityProvider.getHttpEntity(eventPrototype), Void.class);
    }

    public Map<String, Object> getConfiguration() {
        return retryTemplate.execute(context -> Objects.requireNonNull(restTemplate.exchange("/configuration",
                HttpMethod.GET, httpEntityProvider.getEmptyHttpEntity(), new ParameterizedTypeReference<Map<String, Object>>() {}).getBody()));
    }
}
