package cz.lriedel.photo.uploader.processor;

import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;
import cz.lriedel.photo.uploader.HttpEntityProvider;
import cz.lriedel.photo.uploader.fetcher.PhotoFetcher;
import cz.lriedel.photo.uploader.model.Album;
import cz.lriedel.photo.uploader.model.args.ReplacePhotoArgs;
import cz.lriedel.photo.uploader.model.args.UploadPhotosArgs;
import cz.lriedel.photo.uploader.model.request.PhotoPrototype;
import org.springframework.retry.support.RetryTemplate;
import org.springframework.stereotype.Component;
import org.springframework.web.client.RestTemplate;

import java.io.IOException;
import java.nio.channels.FileChannel;
import java.util.Base64;
import java.util.Objects;
import java.util.UUID;
import java.util.concurrent.ExecutionException;

@Component
class ReplacePhotoProcessor extends AbstractProcessor<ReplacePhotoArgs> {

    private static final String REFRESH_ALBUM_ENDPOINT_PATTERN = "/api/places/%s/albums/%s/refresh";
    private static final String CREATE_PHOTO_ENDPOINT_PATTERN = "/api/places/%s/albums/%s/photos";

    private static final String JPG_SUFFIX = ".jpg";

    private final PhotoFetcher photoFetcher;

    public ReplacePhotoProcessor(RestTemplate restTemplate, RetryTemplate retryTemplate, ObjectMapper objectMapper,
                                 HttpEntityProvider httpEntityProvider, PhotoFetcher photoFetcher) {
        super(restTemplate, retryTemplate, objectMapper, httpEntityProvider, ReplacePhotoArgs.class);
        this.photoFetcher = Objects.requireNonNull(photoFetcher);
    }

    @Override
    public void process(ReplacePhotoArgs args) throws Exception {
        uploadPhoto(args);
        refreshAlbum(args);
    }

    private Album refreshAlbum(ReplacePhotoArgs args) {
        logger.info("Uploading has finished. Refreshing the album...");
        String url = String.format(REFRESH_ALBUM_ENDPOINT_PATTERN, args.placeId(), args.albumId());
        return retryTemplate.execute(context -> restTemplate.postForObject(url, httpEntityProvider.getEmptyHttpEntity(), Album.class));
    }

    private void uploadPhoto(ReplacePhotoArgs args) throws IOException {
        logger.info("Uploading a replacement for the photo {}...", args.replacedPhotoId());
        String url = String.format(CREATE_PHOTO_ENDPOINT_PATTERN, args.placeId(), args.albumId());
        PhotoPrototype photoPrototype = new PhotoPrototype(UUID.randomUUID() + JPG_SUFFIX, args.replacedPhotoId(),
                Base64.getEncoder().encodeToString(photoFetcher.fetch(args.path())));
        retryTemplate.execute(context -> restTemplate.postForObject(url, httpEntityProvider.getHttpEntity(photoPrototype), Void.class));
    }
}
