package cz.lriedel.agent.persistance;

import java.time.Instant;

import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Modifying;
import org.springframework.transaction.annotation.Transactional;

public interface UploadedPhotoRepository extends JpaRepository<UploadedPhoto, String> {
    @Modifying
    @Transactional
    long deleteByUploadedBefore(Instant instant);
}
