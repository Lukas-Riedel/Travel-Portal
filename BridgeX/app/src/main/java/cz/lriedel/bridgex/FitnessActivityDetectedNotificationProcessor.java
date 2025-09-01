package cz.lriedel.bridgex;

import androidx.annotation.Nullable;

import java.util.Map;

public class FitnessActivityDetectedNotificationProcessor implements NotificationProcessor {

    @Nullable
    @Override
    public Notification process(Map<String, Object> args) {
        // TODO: i18n
        return new Notification("Fitness aktivita detekována", args.get("intervals").toString(), Map.of());
    }

    @Override
    public String getSupportedEventName() {
        return "FitnessActivityDetected";
    }
}
