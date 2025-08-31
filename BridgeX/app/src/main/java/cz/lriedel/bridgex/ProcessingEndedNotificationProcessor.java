package cz.lriedel.bridgex;

import androidx.annotation.Nullable;

import java.util.Map;

public class ProcessingEndedNotificationProcessor implements NotificationProcessor {

    @Nullable
    @Override
    public Notification process(Map<String, Object> args) {
        if ("PhotosUploadingTriggered".equals(args.get("name"))) {
            @SuppressWarnings("unchecked")
            Map<String, Object> innerArgs = (Map<String, Object>) args.get("args");
            // TODO: i18n
            return new Notification("Fotky byly nahrány", "Místo " + innerArgs.get("placeName") + " má nové fotky",
                    Map.of("placeId", innerArgs.get("placeId")));
        }
        else if ("PhotoReplacingTriggered".equals(args.get("name"))) {
            @SuppressWarnings("unchecked")
            Map<String, Object> innerArgs = (Map<String, Object>) args.get("args");
            // TODO: i18n
            return new Notification("Fotka byla nahrazena", "Místo " + innerArgs.get("placeName") + " má novou fotku",
                    Map.of("placeId", innerArgs.get("placeId")));
        }
        return null;
    }

    @Override
    public String getSupportedEventName() {
        return "ProcessingEnded";
    }
}
