package cz.lriedel.agent;

import cz.lriedel.agent.model.args.AgentShutdownRequestedEventArgs;
import cz.lriedel.agent.model.args.FolderSynchronizationRequestedEventArgs;
import cz.lriedel.agent.model.args.PhotoReplacingTriggeredEventArgs;
import cz.lriedel.agent.model.args.PhotosUploadingCompletedEventArgs;
import cz.lriedel.agent.model.args.PhotosUploadingTriggeredEventArgs;
import cz.lriedel.agent.photo.PhotoService;
import lombok.extern.slf4j.Slf4j;
import org.springframework.amqp.rabbit.annotation.RabbitHandler;
import org.springframework.amqp.rabbit.annotation.RabbitListener;
import org.springframework.stereotype.Component;

@Slf4j
@Component
@RabbitListener(queues = "#{@agentQueueName}")
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
        photoService.uploadPhotos(args.placeId(), args.timestamp(), args.albumId(), args.mainPhotoPosition(), args.path());
    }

    @RabbitHandler
    public void onPhotosUploadingCompleted(PhotosUploadingCompletedEventArgs args) {
        log.info("Received a request to complete upload...");
        photoService.completeUpload(args.batchId());
    }

    @RabbitHandler
    public void onFolderSynchronizationRequested(FolderSynchronizationRequestedEventArgs args) {
        log.info("Received a request to synchronize a folder...");
        photoService.requestFolderSynchronization(args.path(), args.expiration());
    }

    @RabbitHandler
    public void onAgentShutdownRequested(AgentShutdownRequestedEventArgs args) {
        log.info("Received a request to shutdown the agent...");
        // TODO: Kill the application only if there is no other event being processed.
        System.exit(0);
    }
}
