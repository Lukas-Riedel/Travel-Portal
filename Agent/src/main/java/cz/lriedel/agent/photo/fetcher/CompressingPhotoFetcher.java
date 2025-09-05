package cz.lriedel.agent.photo.fetcher;

import java.awt.image.BufferedImage;
import java.io.ByteArrayOutputStream;
import java.nio.file.Path;
import java.util.Locale;

import javax.imageio.IIOImage;
import javax.imageio.ImageIO;
import javax.imageio.ImageReader;
import javax.imageio.ImageWriteParam;
import javax.imageio.ImageWriter;
import javax.imageio.metadata.IIOMetadata;
import javax.imageio.plugins.jpeg.JPEGImageWriteParam;
import javax.imageio.stream.ImageInputStream;
import javax.imageio.stream.MemoryCacheImageOutputStream;

import org.apache.commons.lang3.Validate;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.boot.autoconfigure.condition.ConditionalOnMissingBean;
import org.springframework.stereotype.Component;

import lombok.SneakyThrows;

@Component
@ConditionalOnMissingBean(StandardPhotoFetcher.class)
final class CompressingPhotoFetcher implements PhotoFetcher {

    private static final String JPG_FORMAT_NAME = "jpg";

    private final float outputQuality;

    CompressingPhotoFetcher(@Value("${output.quality}") float outputQuality) {
        this.outputQuality = outputQuality;
        Validate.isTrue(outputQuality > 0 && outputQuality < 1);
    }

    @SneakyThrows
    @Override
    public byte[] fetch(Path path) {
        ImageReader reader = ImageIO.getImageReadersByFormatName(JPG_FORMAT_NAME).next();
        try (ImageInputStream iis = ImageIO.createImageInputStream(path.toFile())) {
            reader.setInput(iis, true);
            BufferedImage image = reader.read(0);
            IIOMetadata metadata = reader.getImageMetadata(0);

            ImageWriter jpgWriter = ImageIO.getImageWritersByFormatName("jpg").next();
            JPEGImageWriteParam jpgWriteParam = new JPEGImageWriteParam(Locale.getDefault());
            jpgWriteParam.setCompressionMode(ImageWriteParam.MODE_EXPLICIT);
            jpgWriteParam.setCompressionQuality(outputQuality);
            jpgWriteParam.setProgressiveMode(ImageWriteParam.MODE_DEFAULT);

            ByteArrayOutputStream outputStream = new ByteArrayOutputStream();
            jpgWriter.setOutput(new MemoryCacheImageOutputStream(outputStream));
            jpgWriter.write(null, new IIOImage(image, null, metadata), jpgWriteParam);
            jpgWriter.dispose();

            return outputStream.toByteArray();
        }
    }
}
