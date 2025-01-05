package cz.lriedel.agent.job;

import com.fasterxml.jackson.databind.ObjectMapper;
import cz.lriedel.agent.model.args.PhotoReplacingEventArgs;
import cz.lriedel.agent.photo.PhotoService;
import org.springframework.stereotype.Component;

@Component
final class PhotoReplacingEventHandler extends AbstractEventHandler<PhotoReplacingEventArgs> {

    private final PhotoService photoFetcher;

    PhotoReplacingEventHandler(ObjectMapper objectMapper, PhotoService photoFetcher) {
        super(objectMapper, PhotoReplacingEventArgs.class);
        this.photoFetcher = photoFetcher;
    }

    @Override
    public void handle(PhotoReplacingEventArgs args) {
        photoFetcher.replacePhoto(args.placeId(), args.albumId(), args.replacedPhotoId(), args.path());
    }
}
