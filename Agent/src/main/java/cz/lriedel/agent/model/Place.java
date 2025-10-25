package cz.lriedel.agent.model;

import java.util.List;

public record Place(String id, String name, List<Date> dates) {
}
