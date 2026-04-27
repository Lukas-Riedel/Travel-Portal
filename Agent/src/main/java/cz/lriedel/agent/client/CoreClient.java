package cz.lriedel.agent.client;

import cz.lriedel.agent.model.api.Album;
import cz.lriedel.agent.model.api.PendingPhoto;
import cz.lriedel.agent.model.api.Place;
import cz.lriedel.agent.model.request.DevicePrototype;
import cz.lriedel.agent.model.request.EventPrototype;
import cz.lriedel.agent.model.request.PhotoPrototype;
import lombok.SneakyThrows;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.core.ParameterizedTypeReference;
import org.springframework.http.HttpMethod;
import org.springframework.stereotype.Component;
import org.springframework.web.client.RestTemplate;
import org.springframework.web.util.UriBuilder;
import org.springframework.web.util.UriBuilderFactory;

import javax.annotation.Nullable;
import java.net.URI;
import java.time.Instant;
import java.util.Base64;
import java.util.List;
import java.util.Map;
import java.util.Objects;

import static cz.lriedel.agent.AgentApplicationConfiguration.CORE_SERVICE_QUALIFIER;

@Component
public class CoreClient {

    private static final String GET_CONFIGURATION_ENDPOINT_PATH = "/configuration";
    private static final String CREATE_EVENT_ENDPOINT_PATH = "/events";
    private static final String GET_PLACES_ENDPOINT_PATH = "/places";
    private static final String CREATE_DEVICE_ENDPOINT_PATH = "/devices";
    private static final String CREATE_PLACE_ALBUM_ENDPOINT_PATH = "/places/{placeId}/albums";
    private static final String REFRESH_PLACE_ALBUM_ENDPOINT_PATH = "/places/{placeId}/albums/{albumId}/refresh";
    private static final String CREATE_PLACE_ALBUM_PHOTO_ENDPOINT_PATH = "/places/{placeId}/albums/{albumId}/photos";

    private static final ParameterizedTypeReference<List<Place>> PLACES_LIST_TYPE_REFERENCE = new ParameterizedTypeReference<>() {

    };
    private static final ParameterizedTypeReference<Map<String, Object>> CONFIGURATION_MAP_TYPE_REFERENCE = new ParameterizedTypeReference<>() {

    };

    private final RestTemplate restTemplate;
    private final HttpEntityProvider httpEntityProvider;
    private final UriBuilderFactory uriBuilderFactory;

    public CoreClient(@Qualifier(CORE_SERVICE_QUALIFIER) RestTemplate restTemplate, HttpEntityProvider httpEntityProvider,
            @Qualifier(CORE_SERVICE_QUALIFIER) UriBuilderFactory uriBuilderFactory) {
        this.restTemplate = restTemplate;
        this.httpEntityProvider = httpEntityProvider;
        this.uriBuilderFactory = uriBuilderFactory;
    }

    public List<Place> getPlaces() {
        URI uri = uriBuilderFactory.builder().path(GET_PLACES_ENDPOINT_PATH).queryParam("include", "dates").build();

        return Objects.requireNonNull(
                restTemplate.exchange(uri, HttpMethod.GET, httpEntityProvider.getEmptyHttpEntity(), PLACES_LIST_TYPE_REFERENCE).getBody());
    }

    @SneakyThrows
    public void registerDevice(String id, Map<String, Object> data) {
        DevicePrototype devicePrototype = DevicePrototype.builder().id(id).data(data).build();

        restTemplate.postForObject(CREATE_DEVICE_ENDPOINT_PATH, httpEntityProvider.getHttpEntity(devicePrototype), Void.class);
    }

    public Album createAlbum(String placeId, Instant timestamp) {
        URI uri = uriBuilderFactory.builder().path(CREATE_PLACE_ALBUM_ENDPOINT_PATH).queryParam("timestamp", timestamp.getEpochSecond())
                .build(placeId);

        return Objects.requireNonNull(restTemplate.postForObject(uri, httpEntityProvider.getEmptyHttpEntity(), Album.class));
    }

    public PendingPhoto uploadPhoto(String placeId, String albumId, String fileName, String replacedPhotoId, byte[] data) {
        PhotoPrototype photoPrototype = PhotoPrototype.builder().fileName(fileName).replacedPhotoId(replacedPhotoId)
                .data(Base64.getEncoder().encodeToString(data)).build();

        URI uri = uriBuilderFactory.builder().path(CREATE_PLACE_ALBUM_PHOTO_ENDPOINT_PATH).build(placeId, albumId);

        return Objects.requireNonNull(restTemplate.postForObject(uri, httpEntityProvider.getHttpEntity(photoPrototype), PendingPhoto.class));
    }

    public PendingPhoto uploadPhoto(String placeId, String albumId, String fileName, String batchId, int expectedBatchSize, int batchPosition, byte[] data) {
        PhotoPrototype photoPrototype = PhotoPrototype.builder().fileName(fileName).batchId(batchId).expectedBatchSize(expectedBatchSize)
                .batchPosition(batchPosition).data(Base64.getEncoder().encodeToString(data)).build();

        URI uri = uriBuilderFactory.builder().path(CREATE_PLACE_ALBUM_PHOTO_ENDPOINT_PATH).build(placeId, albumId);

        return Objects.requireNonNull(restTemplate.postForObject(uri, httpEntityProvider.getHttpEntity(photoPrototype), PendingPhoto.class));
    }

    public Album refreshAlbum(String placeId, String albumId, @Nullable Integer mainPhotoPosition, @Nullable String batchId) {
        UriBuilder uriBuilder = uriBuilderFactory.builder().path(REFRESH_PLACE_ALBUM_ENDPOINT_PATH);

        if (mainPhotoPosition != null) {
            uriBuilder.queryParam("mainPhotoPosition", mainPhotoPosition);
        }

        if (batchId != null) {
            uriBuilder.queryParam("batchId", batchId);
        }

        URI uri = uriBuilder.build(placeId, albumId);

        return Objects.requireNonNull(restTemplate.postForObject(uri, httpEntityProvider.getEmptyHttpEntity(), Album.class));
    }

    public void createEvent(String name, Map<String, Object> args) {
        EventPrototype eventPrototype = EventPrototype.builder().name(name).args(args).build();

        restTemplate.postForObject(CREATE_EVENT_ENDPOINT_PATH, httpEntityProvider.getHttpEntity(eventPrototype), Void.class);
    }

    public Map<String, Object> getConfiguration() {
        return Objects.requireNonNull(restTemplate.exchange(GET_CONFIGURATION_ENDPOINT_PATH, HttpMethod.GET, httpEntityProvider.getEmptyHttpEntity(),
                CONFIGURATION_MAP_TYPE_REFERENCE).getBody());
    }
}
