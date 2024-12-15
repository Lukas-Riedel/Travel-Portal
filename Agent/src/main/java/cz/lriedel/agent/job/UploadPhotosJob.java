package cz.lriedel.agent.job;

import static java.util.Comparator.comparing;

import cz.lriedel.agent.photo.PhotoService;
import org.springframework.stereotype.Component;

import com.fasterxml.jackson.databind.ObjectMapper;

import cz.lriedel.agent.model.args.UploadPhotosArgs;

@Component
final class UploadPhotosJob extends AbstractJob<UploadPhotosArgs> {

    private final PhotoService photoService;

    UploadPhotosJob(ObjectMapper objectMapper, PhotoService photoService) {
        super(objectMapper, UploadPhotosArgs.class);
        this.photoService = photoService;
    }

    @Override
    public void run(UploadPhotosArgs args) {
        photoService.uploadPhotos(args.placeId(), args.timestamp(), args.albumId(), args.mainPhotoPosition(), args.path());
    }
}
