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
    @Column(name = "hash")
    private String hash;

    @Column(name = "uploaded")
    private Instant uploaded;
}
