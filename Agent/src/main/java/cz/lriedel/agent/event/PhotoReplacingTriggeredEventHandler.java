package cz.lriedel.agent.event;

import com.fasterxml.jackson.databind.ObjectMapper;

import cz.lriedel.agent.client.ServiceClient;
import cz.lriedel.agent.model.args.PhotoReplacingTriggeredEventArgs;
import cz.lriedel.agent.photo.PhotoService;
import org.springframework.stereotype.Component;

@Component
final class PhotoReplacingTriggeredEventHandler extends AbstractEventHandler<PhotoReplacingTriggeredEventArgs> {

    private final PhotoService photoService;

    PhotoReplacingTriggeredEventHandler(ObjectMapper objectMapper, ServiceClient serviceClient, PhotoService photoService) {
        super(objectMapper, serviceClient, PhotoReplacingTriggeredEventArgs.class);
        this.photoService = photoService;
    }

    @Override
    public void handle(PhotoReplacingTriggeredEventArgs args) {
        photoService.replacePhoto(args.placeId(), args.albumId(), args.replacedPhotoId(), args.path());
    }
}
