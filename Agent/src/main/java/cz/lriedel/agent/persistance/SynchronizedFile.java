package cz.lriedel.agent.persistance;

import jakarta.persistence.Column;
import jakarta.persistence.Entity;
import jakarta.persistence.Id;
import lombok.AllArgsConstructor;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.Instant;

@Entity
@NoArgsConstructor
@AllArgsConstructor
@Getter
@Setter
public class SynchronizedFile {

    @Id
    @Column(name = "path")
    private String path;

    @Column(name = "size")
    private Long size;

    @Column(name = "uploaded")
    private Instant uploaded;
}
