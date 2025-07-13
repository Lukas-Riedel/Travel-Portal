package cz.lriedel.agent.event;

import static java.util.Comparator.comparing;

import cz.lriedel.agent.photo.PhotoService;
import org.springframework.stereotype.Component;

import com.fasterxml.jackson.databind.ObjectMapper;

import cz.lriedel.agent.model.args.PhotosUploadingTriggeredEventArgs;

@Component
final class PhotosUploadingTriggeredEventHandler extends AbstractEventHandler<PhotosUploadingTriggeredEventArgs> {

    private final PhotoService photoService;

    PhotosUploadingTriggeredEventHandler(ObjectMapper objectMapper, PhotoService photoService) {
        super(objectMapper, PhotosUploadingTriggeredEventArgs.class);
        this.photoService = photoService;
    }

    @Override
    public void handle(PhotosUploadingTriggeredEventArgs args) {
        photoService.uploadPhotos(args.placeId(), args.timestamp(), args.albumId(), args.mainPhotoPosition(), args.path());
    }
}
