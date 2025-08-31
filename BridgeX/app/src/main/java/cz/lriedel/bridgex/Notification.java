package cz.lriedel.bridgex;

import java.util.Map;

public record Notification(String title, String body, Map<String, Object> intentExtras) {

    public Notification(String title, String body) {
        this(title, body, Map.of());
    }
}
