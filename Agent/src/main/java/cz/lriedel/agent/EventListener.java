package cz.lriedel.agent;

import org.springframework.amqp.rabbit.annotation.RabbitHandler;
import org.springframework.amqp.rabbit.annotation.RabbitListener;
import org.springframework.stereotype.Component;

import cz.lriedel.agent.model.args.PhotoReplacingTriggeredEventArgs;
import cz.lriedel.agent.model.args.PhotosUploadingTriggeredEventArgs;
import cz.lriedel.agent.photo.PhotoService;
import lombok.extern.slf4j.Slf4j;

@Slf4j
@Component
@RabbitListener(queues = "${queue.agent.name}")
class EventListener {

    private final PhotoService photoService;

    EventListener(PhotoService photoService) {
        this.photoService = photoService;
    }

    @RabbitHandler
    public void onPhotoReplacingTriggered(PhotoReplacingTriggeredEventArgs args) {
        log.info("Received a request to replace a photo...");
        photoService.replacePhoto(args.placeId(), args.albumId(), args.replacedPhotoId(), args.path());
    }

    @RabbitHandler
    public void onPhotosUploadingTriggered(PhotosUploadingTriggeredEventArgs args) {
        log.info("Received a request to upload photos...");
        photoService.uploadPhotos(args.placeId(), args.timestamp(), args.albumId(),
            args.mainPhotoPosition(), args.path());
    }
}
