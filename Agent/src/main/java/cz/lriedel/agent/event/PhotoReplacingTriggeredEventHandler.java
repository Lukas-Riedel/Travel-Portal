package cz.lriedel.agent.event;

import com.fasterxml.jackson.databind.ObjectMapper;
import cz.lriedel.agent.model.args.PhotoReplacingTriggeredEventArgs;
import cz.lriedel.agent.photo.PhotoService;
import org.springframework.stereotype.Component;

@Component
final class PhotoReplacingTriggeredEventHandler extends AbstractEventHandler<PhotoReplacingTriggeredEventArgs> {

    private final PhotoService photoFetcher;

    PhotoReplacingTriggeredEventHandler(ObjectMapper objectMapper, PhotoService photoFetcher) {
        super(objectMapper, PhotoReplacingTriggeredEventArgs.class);
        this.photoFetcher = photoFetcher;
    }

    @Override
    public void handle(PhotoReplacingTriggeredEventArgs args) {
        photoFetcher.replacePhoto(args.placeId(), args.albumId(), args.replacedPhotoId(), args.path());
    }
}
