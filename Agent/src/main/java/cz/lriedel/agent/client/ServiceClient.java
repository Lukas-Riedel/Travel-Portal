package cz.lriedel.agent.client;

import java.util.Base64;
import java.util.Map;
import java.util.Objects;

import org.springframework.retry.support.RetryTemplate;
import org.springframework.stereotype.Component;
import org.springframework.web.client.RestTemplate;

import cz.lriedel.agent.model.Album;
import cz.lriedel.agent.model.request.EventPrototype;
import cz.lriedel.agent.model.request.PhotoPrototype;

@Component
public final class ServiceClient {

    private final RestTemplate restTemplate;
    private final RetryTemplate retryTemplate;
    private final HttpEntityProvider httpEntityProvider;

    ServiceClient(RestTemplate restTemplate, RetryTemplate retryTemplate, HttpEntityProvider httpEntityProvider) {
        this.restTemplate = restTemplate;
        this.retryTemplate = retryTemplate;
        this.httpEntityProvider = httpEntityProvider;
    }

    public Album createAlbum(long placeId, long timestamp) {
        return Objects.requireNonNull(restTemplate.postForObject(
                "/places/" + placeId + "/albums?timestamp=" + timestamp,
                httpEntityProvider.getEmptyHttpEntity(), Album.class));
    }

    public void uploadPhoto(long placeId, long albumId, String fileName, long replacedPhotoId, byte[] data) {
        PhotoPrototype photoPrototype = new PhotoPrototype(fileName, replacedPhotoId, Base64.getEncoder().encodeToString(data));
        retryTemplate.execute(context -> restTemplate.postForObject(
                "/places/" + placeId + "/albums/" + albumId + "/photos",
                httpEntityProvider.getHttpEntity(photoPrototype), Void.class));
    }

    public void uploadPhoto(long placeId, long albumId, String fileName, String batchId, int expectedBatchSize, int batchPosition, byte[] data) {
        PhotoPrototype photoPrototype = new PhotoPrototype(fileName, batchId, expectedBatchSize, batchPosition, Base64.getEncoder().encodeToString(data));
        retryTemplate.execute(context -> restTemplate.postForObject(
                "/places/" + placeId + "/albums/" + albumId + "/photos",
                httpEntityProvider.getHttpEntity(photoPrototype), Void.class));
    }

    public Album refreshAlbum(long placeId, long albumId) {
        return retryTemplate.execute(context -> Objects.requireNonNull(restTemplate.postForObject(
                "/places/" + placeId + "/albums/" + albumId + "/refresh",
                httpEntityProvider.getEmptyHttpEntity(), Album.class)));
    }

    public Album refreshAlbum(long placeId, long albumId, int mainPhotoPosition) {
        return retryTemplate.execute(context -> Objects.requireNonNull(restTemplate.postForObject(
                "/places/" + placeId + "/albums/" + albumId + "/refresh?mainPhotoPosition=" + mainPhotoPosition,
                httpEntityProvider.getEmptyHttpEntity(), Album.class)));
    }

    public void createEvent(String name, Map<String, Object> args) {
        EventPrototype eventPrototype = new EventPrototype(name, args);
        restTemplate.postForObject(
                "/events",
                httpEntityProvider.getHttpEntity(eventPrototype), Void.class);
    }
}
