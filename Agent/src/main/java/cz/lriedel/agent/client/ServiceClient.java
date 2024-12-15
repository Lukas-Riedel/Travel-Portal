package cz.lriedel.agent.client;

import cz.lriedel.agent.model.Album;
import cz.lriedel.agent.model.Job;
import cz.lriedel.agent.model.request.AlbumPrototype;
import cz.lriedel.agent.model.request.PhotoPrototype;
import lombok.extern.slf4j.Slf4j;
import org.springframework.http.HttpMethod;
import org.springframework.retry.support.RetryTemplate;
import org.springframework.stereotype.Component;
import org.springframework.web.client.RestTemplate;
import org.springframework.web.util.UriComponentsBuilder;

import java.util.Base64;
import java.util.Objects;
import java.util.UUID;

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
        AlbumPrototype albumPrototype = new AlbumPrototype(timestamp);
        return Objects.requireNonNull(restTemplate.postForObject(
                "/api/places/" + placeId + "/albums",
                httpEntityProvider.getHttpEntity(albumPrototype), Album.class));
    }

    public void uploadPhoto(long placeId, long albumId, String fileName, long replacedPhotoId, byte[] data) {
        PhotoPrototype photoPrototype = new PhotoPrototype(fileName, replacedPhotoId, Base64.getEncoder().encodeToString(data));
        retryTemplate.execute(context -> restTemplate.postForObject(
                "/api/places/" + placeId + "/albums/" + albumId + "/photos",
                httpEntityProvider.getHttpEntity(photoPrototype), Void.class));
    }

    public void uploadPhoto(long placeId, long albumId, String fileName, int position, byte[] data) {
        PhotoPrototype photoPrototype = new PhotoPrototype(fileName, position, Base64.getEncoder().encodeToString(data));
        retryTemplate.execute(context -> restTemplate.postForObject(
                "/api/places/" + placeId + "/albums/" + albumId + "/photos",
                httpEntityProvider.getHttpEntity(photoPrototype), Void.class));
    }

    public Album refreshAlbum(long placeId, long albumId) {
        return retryTemplate.execute(context -> Objects.requireNonNull(restTemplate.postForObject(
                "/api/places/" + placeId + "/albums/" + albumId + "/refresh",
                httpEntityProvider.getEmptyHttpEntity(), Album.class)));
    }

    public Album refreshAlbum(long placeId, long albumId, int mainPhotoPosition) {
        return retryTemplate.execute(context -> Objects.requireNonNull(restTemplate.postForObject(
                "/api/places/" + placeId + "/albums/" + albumId + "/refresh?mainPhotoPosition=" + mainPhotoPosition,
                httpEntityProvider.getEmptyHttpEntity(), Album.class)));
    }

    public Job[] listJobs(String jobName) {
        return retryTemplate.execute(context -> Objects.requireNonNull(restTemplate.exchange(
                "/api/jobs/" + jobName,
                HttpMethod.GET, httpEntityProvider.getEmptyHttpEntity(), Job[].class).getBody()));
    }

    public void deleteJob(long jobId) {
        retryTemplate.execute(context -> restTemplate.exchange(
                "/api/jobs/" + jobId,
                HttpMethod.DELETE, httpEntityProvider.getEmptyHttpEntity(), Void.class));
    }
}
