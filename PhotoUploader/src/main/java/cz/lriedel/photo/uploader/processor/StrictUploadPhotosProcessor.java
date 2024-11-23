package cz.lriedel.photo.uploader.processor;

import com.fasterxml.jackson.databind.ObjectMapper;
import cz.lriedel.photo.uploader.HttpEntityProvider;
import cz.lriedel.photo.uploader.fetcher.PhotoFetcher;
import cz.lriedel.photo.uploader.model.args.UploadPhotosArgs;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.retry.support.RetryTemplate;
import org.springframework.web.client.RestTemplate;

//@Component
class StrictUploadPhotosProcessor extends UploadPhotosProcessor {

    private static final Logger LOGGER = LoggerFactory.getLogger(StrictUploadPhotosProcessor.class);

    public StrictUploadPhotosProcessor(RestTemplate restTemplate, RetryTemplate retryTemplate, ObjectMapper objectMapper,
                                       HttpEntityProvider httpEntityProvider, PhotoFetcher photoFetcher) {
        super(restTemplate, retryTemplate, objectMapper, httpEntityProvider, photoFetcher);
    }

    @Override
    public void process(UploadPhotosArgs args) {
        try {
            super.process(args);
        }
        catch (Throwable e) {
            LOGGER.error("Unknown error occurred.", e);
            System.exit(0);
        }
    }
}
