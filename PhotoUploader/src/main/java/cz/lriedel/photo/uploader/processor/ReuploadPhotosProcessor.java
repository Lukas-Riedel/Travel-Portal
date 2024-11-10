package cz.lriedel.photo.uploader.processor;

import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;
import cz.lriedel.photo.uploader.HttpEntityProvider;
import cz.lriedel.photo.uploader.model.Album;
import cz.lriedel.photo.uploader.model.Date;
import cz.lriedel.photo.uploader.model.Photo;
import cz.lriedel.photo.uploader.model.Place;
import cz.lriedel.photo.uploader.model.args.ReuploadPhotosArgs;
import cz.lriedel.photo.uploader.model.args.UploadPhotosArgs;
import cz.lriedel.photo.uploader.model.request.JobPrototype;
import org.springframework.http.HttpMethod;
import org.springframework.stereotype.Component;
import org.springframework.util.FileSystemUtils;
import org.springframework.web.client.RestTemplate;

import java.io.IOException;
import java.net.URL;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.StandardCopyOption;
import java.util.Map;
import java.util.Objects;
import java.util.UUID;

@Component
public class ReuploadPhotosProcessor extends AbstractProcessor<ReuploadPhotosArgs, Album> {

    private static final String GET_PLACE_ENDPOINT_PATTERN = "/api/places/%s";
    private static final String LIST_PHOTOS_ENDPOINT_PATTERN = "/api/places/%s/albums/%s/photos";
    private static final String RUN_JOB_ENDPOINT = "/api/jobs/run";

    private static final String BASE_URL_DOWNLOAD_SUFFIX = "=d";
    private static final String JPG_SUFFIX = ".jpg";

    private static final String FINISH_RE_UPLOAD_PHOTOS_JOB_NAME = "FinishReuploadPhotos";

    private final UploadPhotosProcessor uploadPhotosProcessor;

    public ReuploadPhotosProcessor(RestTemplate restTemplate, ObjectMapper objectMapper,
                                   HttpEntityProvider httpEntityProvider, UploadPhotosProcessor uploadPhotosProcessor) {
        super(restTemplate, objectMapper, httpEntityProvider, ReuploadPhotosArgs.class);
        this.uploadPhotosProcessor = Objects.requireNonNull(uploadPhotosProcessor);
    }

    @Override
    public Album process(ReuploadPhotosArgs args) throws Exception {
        Photo[] photos = fetchPhotos(args);
        Path path = downloadPhotos(photos);
        Album newAlbum = uploadPhotosProcessor.process(getUploadPhotosArgs(args, photos, path));
        finishReUploadPhotosProcessing(args.albumId(), newAlbum.id());
        FileSystemUtils.deleteRecursively(path);
        return newAlbum;
    }

    private Photo[] fetchPhotos(ReuploadPhotosArgs args) throws JsonProcessingException {
        return restTemplate.exchange(String.format(LIST_PHOTOS_ENDPOINT_PATTERN, args.placeId(), args.albumId()),
                HttpMethod.GET, httpEntityProvider.getEmptyHttpEntity(), Photo[].class).getBody();
    }

    private Path downloadPhotos(Photo[] photos) throws IOException {
        Path path = Files.createTempDirectory(getClass().getSimpleName());

        int i = 0;
        for (Photo photo : photos) {
            logger.info("Downloading photo {}/{}...", ++i, photos.length);
            Files.copy(new URL(photo.url() + BASE_URL_DOWNLOAD_SUFFIX).openStream(),
                    path.resolve(UUID.randomUUID() + JPG_SUFFIX), StandardCopyOption.REPLACE_EXISTING);
        }

        return path;
    }

    private UploadPhotosArgs getUploadPhotosArgs(ReuploadPhotosArgs args, Photo[] photos, Path path)
            throws JsonProcessingException {
        Place place = restTemplate.exchange(String.format(GET_PLACE_ENDPOINT_PATTERN, args.placeId()),
                HttpMethod.GET, httpEntityProvider.getEmptyHttpEntity(), Place.class).getBody();

        for (Date date : place.dates()) {
            if (date.album() != null && date.album().id() == args.albumId()) {
                int i = 1;
                for (Photo photo : photos) {
                    if (photo.id() == date.album().mainPhotoId()) {
                        return new UploadPhotosArgs(args.placeId(), date.start(), null, i, path);
                    }
                    ++i;
                }
                logger.warn("The main photo position could not be ontained.");
                return new UploadPhotosArgs(args.placeId(), date.start(), null, 1, path);
            }
        }

        throw new IllegalStateException("Unable to obtain upload arguments for '" + args + "'.");
    }

    private void finishReUploadPhotosProcessing(long oldAlbumId, long newAlbumId) throws JsonProcessingException {
        JobPrototype jobPrototype = new JobPrototype(FINISH_RE_UPLOAD_PHOTOS_JOB_NAME,
                Map.of("oldAlbumId", oldAlbumId, "newAlbumId", newAlbumId));
        restTemplate.postForObject(RUN_JOB_ENDPOINT, httpEntityProvider.getHttpEntity(jobPrototype), Object.class);
    }
}
