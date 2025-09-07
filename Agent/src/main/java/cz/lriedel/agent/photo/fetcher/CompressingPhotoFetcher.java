package cz.lriedel.agent.photo.fetcher;

import java.io.OutputStream;
import java.nio.file.Files;
import java.nio.file.Path;
import java.util.UUID;

import org.apache.commons.imaging.Imaging;
import org.apache.commons.imaging.formats.jpeg.JpegImageMetadata;
import org.apache.commons.imaging.formats.jpeg.exif.ExifRewriter;
import org.apache.commons.imaging.formats.tiff.TiffImageMetadata;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.boot.autoconfigure.condition.ConditionalOnMissingBean;
import org.springframework.stereotype.Component;

import cz.lriedel.agent.MozJpegService;
import lombok.SneakyThrows;
import lombok.extern.slf4j.Slf4j;

@Slf4j
@Component
@ConditionalOnMissingBean(StandardPhotoFetcher.class)
final class CompressingPhotoFetcher implements PhotoFetcher {

    private static final String JPG_FILE_EXTENSION = ".jpg";

    private final MozJpegService mozJpegService;
    private final ExifRewriter exifRewriter;
    private final float outputQuality;

    CompressingPhotoFetcher(MozJpegService mozJpegService, ExifRewriter exifRewriter, @Value("${output.quality}") float outputQuality) {
        this.mozJpegService = mozJpegService;
        this.exifRewriter = exifRewriter;
        this.outputQuality = outputQuality;
    }

    @SneakyThrows
    @Override
    public byte[] fetch(Path path) {
        Path tempOutputWithoutMetadata = Files.createTempFile(UUID.randomUUID().toString(), JPG_FILE_EXTENSION);
        Path tempOutputWithMetadata = Files.createTempFile(UUID.randomUUID().toString(), JPG_FILE_EXTENSION);

        try {
            mozJpegService.compress(path, tempOutputWithoutMetadata, (int)(100 * outputQuality));

            JpegImageMetadata sourceMetadata = (JpegImageMetadata) Imaging.getMetadata(path.toFile());
            TiffImageMetadata imageMetadata = sourceMetadata.getExif();
            if (imageMetadata == null) {
                log.warn("There are no image metadata associated with '{}'.", path);
                return Files.readAllBytes(tempOutputWithoutMetadata);
            }

            try (OutputStream os = Files.newOutputStream(tempOutputWithMetadata)) {
                exifRewriter.updateExifMetadataLossy(tempOutputWithoutMetadata.toFile(), os, imageMetadata.getOutputSet());
            }

            return Files.readAllBytes(tempOutputWithMetadata);
        }
        finally {
            Files.deleteIfExists(tempOutputWithoutMetadata);
            Files.deleteIfExists(tempOutputWithMetadata);
        }
    }
}
