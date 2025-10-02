package cz.lriedel.agent.persistance;

import jakarta.persistence.Column;
import jakarta.persistence.Entity;
import jakarta.persistence.Id;
import jakarta.persistence.Lob;
import lombok.AllArgsConstructor;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

@Entity
@NoArgsConstructor
@AllArgsConstructor
@Getter
@Setter
public class Configuration {

    @Id
    @Column(name = "`key`")
    private String key;

    @Lob
    @Column(name = "`value`")
    private String value;
}