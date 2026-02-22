package cz.lriedel.agent.persistance;

import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Modifying;
import org.springframework.transaction.annotation.Transactional;

import java.time.Instant;

public interface UploadedPhotoRepository extends JpaRepository<UploadedPhoto, String> {

    @Modifying
    @Transactional
    long deleteByUploadedBefore(Instant timestamp);
}
