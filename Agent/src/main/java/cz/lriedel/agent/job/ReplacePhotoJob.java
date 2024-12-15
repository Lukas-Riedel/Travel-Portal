package cz.lriedel.agent.job;

import com.fasterxml.jackson.databind.ObjectMapper;
import cz.lriedel.agent.model.args.ReplacePhotoArgs;
import cz.lriedel.agent.photo.PhotoService;
import org.springframework.stereotype.Component;

@Component
final class ReplacePhotoJob extends AbstractJob<ReplacePhotoArgs> {

    private final PhotoService photoFetcher;

    ReplacePhotoJob(ObjectMapper objectMapper, PhotoService photoFetcher) {
        super(objectMapper, ReplacePhotoArgs.class);
        this.photoFetcher = photoFetcher;
    }

    @Override
    public void run(ReplacePhotoArgs args) {
        photoFetcher.replacePhoto(args.placeId(), args.albumId(), args.replacedPhotoId(), args.path());
    }
}
