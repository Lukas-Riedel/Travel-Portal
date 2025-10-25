package cz.lriedel.agent.persistance;

import java.time.Instant;

import jakarta.persistence.Column;
import jakarta.persistence.Entity;
import jakarta.persistence.Id;
import lombok.AllArgsConstructor;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

@Entity
@NoArgsConstructor
@AllArgsConstructor
@Getter
@Setter
public class UploadedPhoto {

    @Id
    @Column(name = "path")
    private String path;

    @Column(name = "uploaded")
    private Instant uploaded;
}
