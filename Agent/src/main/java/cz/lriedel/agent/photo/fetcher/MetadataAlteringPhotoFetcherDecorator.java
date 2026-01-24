package cz.lriedel.agent.photo.fetcher;

import cz.lriedel.agent.client.CoreClient;
import lombok.SneakyThrows;
import lombok.extern.slf4j.Slf4j;
import org.apache.commons.imaging.Imaging;
import org.apache.commons.imaging.formats.jpeg.JpegImageMetadata;
import org.apache.commons.imaging.formats.jpeg.exif.ExifRewriter;
import org.apache.commons.imaging.formats.tiff.TiffImageMetadata;
import org.apache.commons.imaging.formats.tiff.constants.ExifTagConstants;
import org.apache.commons.imaging.formats.tiff.constants.TiffDirectoryType;
import org.apache.commons.imaging.formats.tiff.taginfos.TagInfoAscii;
import org.apache.commons.imaging.formats.tiff.write.TiffOutputDirectory;
import org.apache.commons.imaging.formats.tiff.write.TiffOutputField;
import org.apache.commons.imaging.formats.tiff.write.TiffOutputSet;
import org.springframework.context.annotation.Primary;
import org.springframework.stereotype.Component;

import java.io.ByteArrayOutputStream;
import java.nio.file.Path;
import java.time.LocalDateTime;
import java.time.ZoneId;
import java.time.format.DateTimeFormatter;
import java.util.Map;

@Slf4j
@Primary
@Component
public class MetadataAlteringPhotoFetcherDecorator implements PhotoFetcher {

    private static final String HOME_LOCATION_CONFIGURATION_KEY = "homeLocation";
    private static final String TIMEZONE_HOME_LOCATION_CONFIGURATION_KEY = "timezone";

    private static final int TAG_OFFSET_TIME = 0x9010;
    private static final int TAG_OFFSET_TIME_ORIGINAL = 0x9011;
    private static final int TAG_OFFSET_TIME_DIGITIZED = 0x9012;

    private static final String TAG_OFFSET_TIME_ORIGINAL_NAME = "OffsetTimeOriginal";

    private static final String EXIF_DATE_TIME_FORMAT = "yyyy:MM:dd HH:mm:ss";

    private final PhotoFetcher photoFetcher;
    private final ExifRewriter exifRewriter;

    private final ZoneId timezone;

    public MetadataAlteringPhotoFetcherDecorator(CoreClient coreClient, PhotoFetcher photoFetcher, ExifRewriter exifRewriter) {
        this.photoFetcher = photoFetcher;
        this.exifRewriter = exifRewriter;
        this.timezone = ZoneId.of(((Map<String, Object>) coreClient.getConfiguration().get(HOME_LOCATION_CONFIGURATION_KEY)).get(
                TIMEZONE_HOME_LOCATION_CONFIGURATION_KEY).toString());
    }

    @SneakyThrows
    @Override
    public byte[] fetch(Path path) {
        byte[] data = photoFetcher.fetch(path);

        JpegImageMetadata sourceMetadata = (JpegImageMetadata) Imaging.getMetadata(path.toFile());
        TiffImageMetadata imageMetadata = sourceMetadata.getExif();
        if (imageMetadata == null) {
            log.warn("There are no image metadata associated with '{}'.", path);
            return data;
        }

        TiffOutputSet outputSet = imageMetadata.getOutputSet();
        TiffOutputDirectory exifDir = outputSet.getOrCreateExifDirectory();

        exifDir.removeField(TAG_OFFSET_TIME);
        exifDir.removeField(TAG_OFFSET_TIME_ORIGINAL);
        exifDir.removeField(TAG_OFFSET_TIME_DIGITIZED);

        TiffOutputField dateTimeField = exifDir.findField(ExifTagConstants.EXIF_TAG_DATE_TIME_ORIGINAL);
        if (dateTimeField != null) {
            String rawDateTime = new String(dateTimeField.getData());

            DateTimeFormatter exifFormatter = DateTimeFormatter.ofPattern(EXIF_DATE_TIME_FORMAT);
            LocalDateTime localDateTime = LocalDateTime.parse(rawDateTime.trim().replaceAll("\u0000", ""), exifFormatter);
            String offset = timezone.getRules().getOffset(localDateTime).getId();

            TagInfoAscii offsetTagInfo = new TagInfoAscii(TAG_OFFSET_TIME_ORIGINAL_NAME, TAG_OFFSET_TIME_ORIGINAL, -1,
                    TiffDirectoryType.EXIF_DIRECTORY_EXIF_IFD);
            exifDir.add(offsetTagInfo, offset);
        }
        else {
            log.warn("Timezone offset is not available for '{}'.", path);
        }

        try (ByteArrayOutputStream os = new ByteArrayOutputStream()) {
            exifRewriter.updateExifMetadataLossy(data, os, outputSet);
            return os.toByteArray();
        }
    }
}
