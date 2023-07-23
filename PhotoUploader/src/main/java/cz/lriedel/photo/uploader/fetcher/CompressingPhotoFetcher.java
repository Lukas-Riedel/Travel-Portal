package cz.lriedel.photo.uploader.fetcher;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.nio.file.Path;

import javax.imageio.IIOImage;
import javax.imageio.ImageIO;
import javax.imageio.ImageReader;
import javax.imageio.ImageWriteParam;
import javax.imageio.ImageWriter;
import javax.imageio.stream.ImageInputStream;
import javax.imageio.stream.MemoryCacheImageOutputStream;

import org.apache.commons.lang.Validate;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.boot.autoconfigure.condition.ConditionalOnMissingBean;
import org.springframework.stereotype.Component;

@Component
@ConditionalOnMissingBean(StandardPhotoFetcher.class)
public class CompressingPhotoFetcher implements PhotoFetcher {

    private final float outputQuality;

    public CompressingPhotoFetcher(@Value("${output.quality}") float outputQuality) {
        this.outputQuality = outputQuality;
        Validate.isTrue(outputQuality > 0 && outputQuality < 1);
    }

    @Override
    public byte[] fetch(Path path) throws IOException {
        try (ImageInputStream iis = ImageIO.createImageInputStream(path.toFile())) {
            ImageReader reader = ImageIO.getImageReadersByFormatName("jpg").next();
            reader.setInput(iis, true);
            IIOImage image = reader.readAll(0, null);
            ByteArrayOutputStream outputStream = new ByteArrayOutputStream();
            ImageWriter jpgWriter = ImageIO.getImageWritersByFormatName("jpg").next();
            ImageWriteParam jpgWriteParam = jpgWriter.getDefaultWriteParam();
            jpgWriteParam.setCompressionMode(ImageWriteParam.MODE_EXPLICIT);
            jpgWriteParam.setCompressionQuality(outputQuality);
            jpgWriter.setOutput(new MemoryCacheImageOutputStream(outputStream));
            jpgWriter.write(null, image, jpgWriteParam);
            return outputStream.toByteArray();
        }
    }
}
