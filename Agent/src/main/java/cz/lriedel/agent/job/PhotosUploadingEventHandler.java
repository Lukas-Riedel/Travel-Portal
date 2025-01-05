package cz.lriedel.agent.job;

import static java.util.Comparator.comparing;

import cz.lriedel.agent.photo.PhotoService;
import org.springframework.stereotype.Component;

import com.fasterxml.jackson.databind.ObjectMapper;

import cz.lriedel.agent.model.args.PhotosUploadingEventArgs;

@Component
final class PhotosUploadingEventHandler extends AbstractEventHandler<PhotosUploadingEventArgs> {

    private final PhotoService photoService;

    PhotosUploadingEventHandler(ObjectMapper objectMapper, PhotoService photoService) {
        super(objectMapper, PhotosUploadingEventArgs.class);
        this.photoService = photoService;
    }

    @Override
    public void handle(PhotosUploadingEventArgs args) {
        photoService.uploadPhotos(args.placeId(), args.timestamp(), args.albumId(), args.mainPhotoPosition(), args.path());
    }
}
